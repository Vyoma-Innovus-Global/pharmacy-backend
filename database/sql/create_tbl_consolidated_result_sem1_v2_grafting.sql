BEGIN;

DROP TABLE IF EXISTS tbl_consolidated_result_sem1_v2;
CREATE TABLE tbl_consolidated_result_sem1_v2
(LIKE tbl_consolidated_result_sem1 INCLUDING ALL);

INSERT INTO tbl_consolidated_result_sem1_v2
SELECT *
FROM tbl_consolidated_result_sem1;

CREATE TEMP TABLE tmp_sem1_subjects ON COMMIT DROP AS
SELECT crs1_id, crs1_registration_number, 1 AS slot_no, crs1_subject1_name AS subject_name, crs1_subject1_code AS subject_code, crs1_subject1_category AS category, crs1_subject1_marks_obtanied AS marks, crs1_subject1_status AS subject_status, crs1_subject1_internal_marks_obtained AS internal_marks, crs1_subject1_internal_full_marks AS internal_full_marks, crs1_subject1_external_marks_obtained AS external_marks, crs1_subject1_external_full_marks AS external_full_marks FROM tbl_consolidated_result_sem1_v2 WHERE crs1_subject1_code IS NOT NULL
UNION ALL
SELECT crs1_id, crs1_registration_number, 2, crs1_subject2_name, crs1_subject2_code, crs1_subject2_category, crs1_subject2_marks_obtanied, crs1_subject2_status, crs1_subject2_internal_marks_obtained, crs1_subject2_internal_full_marks, crs1_subject2_external_marks_obtained, crs1_subject2_external_full_marks FROM tbl_consolidated_result_sem1_v2 WHERE crs1_subject2_code IS NOT NULL
UNION ALL
SELECT crs1_id, crs1_registration_number, 3, crs1_subject3_name, crs1_subject3_code, crs1_subject3_category, crs1_subject3_marks_obtanied, crs1_subject3_status, crs1_subject3_internal_marks_obtained, crs1_subject3_internal_full_marks, crs1_subject3_external_marks_obtained, crs1_subject3_external_full_marks FROM tbl_consolidated_result_sem1_v2 WHERE crs1_subject3_code IS NOT NULL
UNION ALL
SELECT crs1_id, crs1_registration_number, 4, crs1_subject4_name, crs1_subject4_code, crs1_subject4_category, crs1_subject4_marks_obtanied, crs1_subject4_status, crs1_subject4_internal_marks_obtained, crs1_subject4_internal_full_marks, crs1_subject4_external_marks_obtained, crs1_subject4_external_full_marks FROM tbl_consolidated_result_sem1_v2 WHERE crs1_subject4_code IS NOT NULL
UNION ALL
SELECT crs1_id, crs1_registration_number, 5, crs1_subject5_name, crs1_subject5_code, crs1_subject5_category, crs1_subject5_marks_obtanied, crs1_subject5_status, crs1_subject5_internal_marks_obtained, crs1_subject5_internal_full_marks, crs1_subject5_external_marks_obtained, crs1_subject5_external_full_marks FROM tbl_consolidated_result_sem1_v2 WHERE crs1_subject5_code IS NOT NULL
UNION ALL
SELECT crs1_id, crs1_registration_number, 6, crs1_subject6_name, crs1_subject6_code, crs1_subject6_category, crs1_subject6_marks_obtanied, crs1_subject6_status, crs1_subject6_internal_marks_obtained, crs1_subject6_internal_full_marks, crs1_subject6_external_marks_obtained, crs1_subject6_external_full_marks FROM tbl_consolidated_result_sem1_v2 WHERE crs1_subject6_code IS NOT NULL
UNION ALL
SELECT crs1_id, crs1_registration_number, 7, crs1_subject7_name, crs1_subject7_code, crs1_subject7_category, crs1_subject7_marks_obtanied, crs1_subject7_status, crs1_subject7_internal_marks_obtained, crs1_subject7_internal_full_marks, crs1_subject7_external_marks_obtained, crs1_subject7_external_full_marks FROM tbl_consolidated_result_sem1_v2 WHERE crs1_subject7_code IS NOT NULL
UNION ALL
SELECT crs1_id, crs1_registration_number, 8, crs1_subject8_name, crs1_subject8_code, crs1_subject8_category, crs1_subject8_marks_obtanied, crs1_subject8_status, crs1_subject8_internal_marks_obtained, crs1_subject8_internal_full_marks, crs1_subject8_external_marks_obtained, crs1_subject8_external_full_marks FROM tbl_consolidated_result_sem1_v2 WHERE crs1_subject8_code IS NOT NULL
UNION ALL
SELECT crs1_id, crs1_registration_number, 9, crs1_subject9_name, crs1_subject9_code, crs1_subject9_category, crs1_subject9_marks_obtanied, crs1_subject9_status, crs1_subject9_internal_marks_obtained, crs1_subject9_internal_full_marks, crs1_subject9_external_marks_obtained, crs1_subject9_external_full_marks FROM tbl_consolidated_result_sem1_v2 WHERE crs1_subject9_code IS NOT NULL
UNION ALL
SELECT crs1_id, crs1_registration_number, 10, crs1_subject10_name, crs1_subject10_code, crs1_subject10_category, crs1_subject10_marks_obtanied, crs1_subject10_status, crs1_subject10_internal_marks_obtained, crs1_subject10_internal_full_marks, crs1_subject10_external_marks_obtained, crs1_subject10_external_full_marks FROM tbl_consolidated_result_sem1_v2 WHERE crs1_subject10_code IS NOT NULL;

CREATE INDEX tmp_sem1_subjects_student_idx ON tmp_sem1_subjects (crs1_id);
CREATE INDEX tmp_sem1_subjects_theory_idx ON tmp_sem1_subjects (crs1_id, marks DESC, slot_no)
WHERE upper(coalesce(category, '')) = 'THEORY';

CREATE TEMP TABLE tmp_sem1_beneficiaries ON COMMIT DROP AS
SELECT DISTINCT ON (crs1_id)
    *,
    40 - marks AS need_marks
FROM tmp_sem1_subjects
WHERE upper(coalesce(category, '')) = 'THEORY'
    AND marks < 40
    AND 40 - marks BETWEEN 1 AND 10
ORDER BY crs1_id, 40 - marks, slot_no;

CREATE INDEX tmp_sem1_beneficiaries_student_idx ON tmp_sem1_beneficiaries (crs1_id);

CREATE TEMP TABLE tmp_sem1_donors ON COMMIT DROP AS
SELECT DISTINCT ON (beneficiaries.crs1_id)
    beneficiaries.crs1_id,
    beneficiaries.crs1_registration_number,
    beneficiaries.slot_no AS beneficiary_slot,
    beneficiaries.subject_name AS beneficiary_name,
    beneficiaries.subject_code AS beneficiary_code,
    beneficiaries.need_marks,
    coalesce(beneficiaries.internal_marks, 0) AS beneficiary_internal_marks,
    coalesce(beneficiaries.internal_full_marks, 0) AS beneficiary_internal_full_marks,
    coalesce(beneficiaries.external_marks, 0) AS beneficiary_external_marks,
    coalesce(beneficiaries.external_full_marks, 0) AS beneficiary_external_full_marks,
    donors.slot_no AS donor_slot,
    donors.subject_name AS donor_name,
    donors.subject_code AS donor_code,
    donors.marks AS donor_marks,
    coalesce(donors.internal_marks, 0) AS donor_internal_marks,
    coalesce(donors.external_marks, 0) AS donor_external_marks
FROM tmp_sem1_beneficiaries beneficiaries
JOIN tmp_sem1_subjects donors
    ON donors.crs1_id = beneficiaries.crs1_id
    AND donors.slot_no <> beneficiaries.slot_no
    AND upper(coalesce(donors.category, '')) = 'THEORY'
    AND donors.marks - beneficiaries.need_marks >= 40
ORDER BY beneficiaries.crs1_id, donors.marks DESC, donors.slot_no;

CREATE TEMP TABLE tmp_sem1_grafting_decisions ON COMMIT DROP AS
WITH transfer_split AS (
    SELECT
        donors.*,
        least(
            donors.need_marks,
            greatest(donors.beneficiary_external_full_marks - donors.beneficiary_external_marks, 0),
            donors.donor_external_marks
        ) AS add_external
    FROM tmp_sem1_donors donors
),
complete_transfer_split AS (
    SELECT
        transfer_split.*,
        least(
            transfer_split.need_marks - transfer_split.add_external,
            greatest(transfer_split.beneficiary_internal_full_marks - transfer_split.beneficiary_internal_marks, 0),
            transfer_split.donor_internal_marks
        ) AS add_internal
    FROM transfer_split
)
SELECT
    *,
    format(
        'Grafting applied: %s mark(s) transferred from %s (%s) to %s (%s), selected because it required the minimum deficiency to reach pass mark 40.',
        need_marks,
        donor_name,
        donor_code,
        beneficiary_name,
        beneficiary_code
    ) AS graft_reason
FROM complete_transfer_split
WHERE add_external + add_internal = need_marks;

CREATE INDEX tmp_sem1_grafting_decisions_student_idx ON tmp_sem1_grafting_decisions (crs1_id);
CREATE INDEX tmp_sem1_grafting_decisions_donor_slot_idx ON tmp_sem1_grafting_decisions (donor_slot);
CREATE INDEX tmp_sem1_grafting_decisions_beneficiary_slot_idx ON tmp_sem1_grafting_decisions (beneficiary_slot);

DO $$
DECLARE
    slot_no integer;
BEGIN
    FOR slot_no IN 1..10 LOOP
        EXECUTE format(
            'UPDATE tbl_consolidated_result_sem1_v2 result
                SET
                    crs1_subject%s_marks_obtanied = coalesce(crs1_subject%s_marks_obtanied, 0) - decisions.need_marks,
                    crs1_subject%s_internal_marks_obtained = coalesce(crs1_subject%s_internal_marks_obtained, 0) - decisions.add_internal,
                    crs1_subject%s_external_marks_obtained = coalesce(crs1_subject%s_external_marks_obtained, 0) - decisions.add_external,
                    crs1_subject%s_percentage = round(((coalesce(crs1_subject%s_marks_obtanied, 0) - decisions.need_marks) / nullif(crs1_subject%s_full_marks, 0)) * 100, 2),
                    crs1_subject%s_grafting_marks = decisions.need_marks,
                    crs1_subject%s_grafting_logic = %L,
                    crs1_subject%s_grafting_reason = decisions.graft_reason,
                    crs1_grafting_remarks = decisions.graft_reason
              FROM tmp_sem1_grafting_decisions decisions
              WHERE result.crs1_id = decisions.crs1_id
                AND decisions.donor_slot = %s',
            slot_no, slot_no,
            slot_no, slot_no,
            slot_no, slot_no,
            slot_no, slot_no, slot_no,
            slot_no,
            slot_no, 'DEDUCTED',
            slot_no,
            slot_no
        );

        EXECUTE format(
            'UPDATE tbl_consolidated_result_sem1_v2 result
                SET
                    crs1_subject%s_marks_obtanied = coalesce(crs1_subject%s_marks_obtanied, 0) + decisions.need_marks,
                    crs1_subject%s_internal_marks_obtained = coalesce(crs1_subject%s_internal_marks_obtained, 0) + decisions.add_internal,
                    crs1_subject%s_external_marks_obtained = coalesce(crs1_subject%s_external_marks_obtained, 0) + decisions.add_external,
                    crs1_subject%s_percentage = round(((coalesce(crs1_subject%s_marks_obtanied, 0) + decisions.need_marks) / nullif(crs1_subject%s_full_marks, 0)) * 100, 2),
                    crs1_subject%s_status = %L,
                    crs1_subject%s_grafting_marks = decisions.need_marks,
                    crs1_subject%s_grafting_logic = %L,
                    crs1_subject%s_grafting_reason = decisions.graft_reason,
                    crs1_grafting_remarks = decisions.graft_reason
              FROM tmp_sem1_grafting_decisions decisions
              WHERE result.crs1_id = decisions.crs1_id
                AND decisions.beneficiary_slot = %s',
            slot_no, slot_no,
            slot_no, slot_no,
            slot_no, slot_no,
            slot_no, slot_no, slot_no,
            slot_no, 'PASS',
            slot_no,
            slot_no, 'ADDED',
            slot_no,
            slot_no
        );
    END LOOP;
END $$;

COMMIT;
