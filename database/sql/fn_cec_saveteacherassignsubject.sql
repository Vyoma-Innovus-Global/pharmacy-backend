CREATE OR REPLACE FUNCTION public.fn_cec_saveteacherassignsubject(
    p_answerscript_id bigint,
    p_teacher_id bigint,
    p_semester_id character varying,
    p_entry_user_id bigint,
    p_inst_code character varying,
    p_examinertype_id integer,
    p_examyear character varying,
    p_subject_code character varying
)
 RETURNS json
 LANGUAGE plpgsql
AS $function$

DECLARE
    v_assignment_id BIGINT;

    v_inst_id BIGINT;
    v_subject_id BIGINT;

    v_full_name TEXT;
    v_phone TEXT;

    v_examiner_type TEXT;

    v_sem INT;

    v_sqlstate TEXT;
    v_msgtext TEXT;

BEGIN

    ------------------------------------------------------------------
    -- GET SEMESTER
    ------------------------------------------------------------------
    IF p_semester_id = 'Part-I' OR p_semester_id = '1' THEN

        v_sem := 1;
        p_semester_id := 'Part-I';

    ELSIF p_semester_id = 'Part-II' OR p_semester_id = '2' THEN

        v_sem := 2;
        p_semester_id := 'Part-II';

    ELSE

        RETURN json_build_object(
            'p_errorcode', 2,
            'p_message', 'Invalid semester'
        );

    END IF;

    ------------------------------------------------------------------
    -- GET TEACHER INFORMATION
    ------------------------------------------------------------------
    SELECT
        au_fullname,
        au_phone
    INTO
        v_full_name,
        v_phone
    FROM public.tbl_admin_users
    WHERE au_id = p_teacher_id
    LIMIT 1;

    IF v_full_name IS NULL THEN

        RETURN json_build_object(
            'p_errorcode', 4,
            'p_message', 'Teacher not found'
        );

    END IF;

    ------------------------------------------------------------------
    -- GET EXAMINER TYPE
    ------------------------------------------------------------------
    SELECT
        extype_code
    INTO
        v_examiner_type
    FROM public.tbl_examiner_types
    WHERE extype_id = p_examinertype_id
    LIMIT 1;

    ------------------------------------------------------------------
    -- RESOLVE INST ID AND SUBJECT ID (IF AVAILABLE)
    ------------------------------------------------------------------
    SELECT
        asi_inst_id,
        asi_subject_id
    INTO
        v_inst_id,
        v_subject_id
    FROM public.tbl_answer_script_intake
    WHERE asi_answer_script_id = p_answerscript_id
    LIMIT 1;

    IF v_inst_id IS NULL AND p_inst_code IS NOT NULL THEN
        SELECT i_id INTO v_inst_id
        FROM public.institute_master
        WHERE UPPER(TRIM(i_code)) = UPPER(TRIM(p_inst_code))
        LIMIT 1;
    END IF;

    IF v_subject_id IS NULL AND p_subject_code IS NOT NULL THEN
        SELECT dsm_id INTO v_subject_id
        FROM public.tbl_department_subjects_master
        WHERE UPPER(TRIM(dsm_subject_id)) = UPPER(TRIM(p_subject_code))
        LIMIT 1;
    END IF;

    ------------------------------------------------------------------
    -- FIND EXISTING ASSIGNMENT
    --
    -- Check whether an assignment slot already exists for this inst + subject + sem + examiner type + exam year.
    ------------------------------------------------------------------
    SELECT
        aua_id
    INTO
        v_assignment_id
    FROM public.tbl_admin_user_assignments
    WHERE aua_inst_code = p_inst_code
      AND aua_sem = p_semester_id
      AND aua_subject_code = p_subject_code
      AND aua_examiner_type_id = p_examinertype_id
      AND aua_role_id = 9
      AND aua_exam_year = p_examyear
      AND aua_isactive = 1
    LIMIT 1;

    ------------------------------------------------------------------
    -- EXISTING ASSIGNMENT: Update teacher
    ------------------------------------------------------------------
    IF v_assignment_id IS NOT NULL THEN

        UPDATE public.tbl_admin_user_assignments
        SET
            aua_user_id = p_teacher_id,
            aua_full_name = v_full_name,
            aua_phone = v_phone,
            aua_assigned_by = p_entry_user_id,
            aua_assigned_at = CURRENT_TIMESTAMP,
            aua_updated_at = CURRENT_TIMESTAMP,
            aua_update_by = p_entry_user_id,
            aua_isactive = 1
        WHERE aua_id = v_assignment_id;

    ------------------------------------------------------------------
    -- NEW ASSIGNMENT: Insert
    ------------------------------------------------------------------
    ELSE

        INSERT INTO public.tbl_admin_user_assignments
        (
            aua_inst_id,
            aua_inst_code,
            aua_user_id,
            aua_full_name,
            aua_phone,
            aua_role_id,
            aua_department_id,
            aua_department_code,
            aua_sem,
            aua_subject_id,
            aua_subject_code,
            aua_examiner_type_id,
            aua_examiner_type,
            aua_assigned_by,
            aua_assigned_at,
            aua_isactive,
            aua_created_at,
            aua_exam_year,
            aua_semester_id
        )
        VALUES
        (
            v_inst_id,
            p_inst_code,
            p_teacher_id,
            v_full_name,
            v_phone,
            9,
            1,
            'PHARM',
            p_semester_id,
            v_subject_id,
            p_subject_code,
            p_examinertype_id,
            v_examiner_type,
            p_entry_user_id,
            CURRENT_TIMESTAMP,
            1,
            CURRENT_TIMESTAMP,
            p_examyear,
            v_sem
        );

    END IF;

    ------------------------------------------------------------------
    -- UPDATE ANSWER SCRIPT INTAKE
    ------------------------------------------------------------------
    UPDATE public.tbl_answer_script_intake
    SET
        asi_examiner_user_id = p_teacher_id,
        asi_examiner_assigned_on = CURRENT_TIMESTAMP
    WHERE asi_answer_script_id = p_answerscript_id;

    ------------------------------------------------------------------
    -- SUCCESS
    ------------------------------------------------------------------
    RETURN json_build_object(
        'p_errorcode', 0,
        'p_message', 'Teacher assigned successfully'
    );

EXCEPTION
    WHEN OTHERS THEN

        GET STACKED DIAGNOSTICS
            v_sqlstate = RETURNED_SQLSTATE,
            v_msgtext = MESSAGE_TEXT;

        PERFORM public.fn_savedboperationerror(
            'fn_cec_saveteacherassignsubject',
            v_sqlstate,
            NULL,
            v_msgtext
        );

        RETURN json_build_object(
            'p_errorcode', 1,
            'p_errormsg', v_msgtext
        );

END;
$function$;
