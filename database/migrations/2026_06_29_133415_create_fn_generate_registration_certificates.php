<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE FUNCTION public.fn_generate_registration_certificates(
                p_sess_yr VARCHAR,
                p_reg_numbers VARCHAR[],
                p_issue_date VARCHAR,
                p_certificate_type VARCHAR
            )
            RETURNS JSONB AS $$
            DECLARE
                v_st_tbl VARCHAR := 'tbl_student_master';
                v_not_issued VARCHAR[];
                v_reg_no VARCHAR;
                v_result JSONB;
                v_missing_count INT := 0;
            BEGIN
                -- 1. Check for missing issue records if type is original
                IF p_certificate_type = 'original' THEN
                    -- Find registration numbers that don't have an issue record for this session & type
                    SELECT COALESCE(array_agg(r), '{}')
                    INTO v_not_issued
                    FROM unnest(p_reg_numbers) r
                    WHERE NOT EXISTS (
                        SELECT 1 
                        FROM public.registration_certificate_issue 
                        WHERE reg_no = r 
                          AND sess_year = p_sess_yr 
                          AND certificate_type = p_certificate_type
                          AND reg_issued_on IS NOT NULL
                          AND reg_issued_on <> ''
                    );

                    v_missing_count := array_length(v_not_issued, 1);

                    -- If missing records and no issue date provided, return list of missing numbers
                    IF v_missing_count > 0 AND (p_issue_date IS NULL OR p_issue_date = '') THEN
                        RETURN jsonb_build_object(
                            'error', true,
                            'message', 'Following registration numbers have no issue date. Please provide an issue date to create their records.',
                            'missing_reg_nos', to_jsonb(v_not_issued)
                        );
                    END IF;

                    -- If issue date is provided, insert missing records
                    IF v_missing_count > 0 AND p_issue_date IS NOT NULL AND p_issue_date <> '' THEN
                        FOREACH v_reg_no IN ARRAY v_not_issued LOOP
                            -- Check if it doesn't exist at all to prevent duplicates
                            IF NOT EXISTS (
                                SELECT 1 FROM public.registration_certificate_issue 
                                WHERE reg_no = v_reg_no
                                  AND sess_year = p_sess_yr 
                                  AND certificate_type = p_certificate_type
                            ) THEN
                                INSERT INTO public.registration_certificate_issue (
                                    sess_year,
                                    reg_year,
                                    inst_code,
                                    dept_code,
                                    reg_no,
                                    reg_issued_on,
                                    certificate_type,
                                    is_download
                                ) VALUES (
                                    p_sess_yr,
                                    split_part(p_sess_yr, '-', 2),
                                    (SELECT sm_inst_code FROM public.tbl_student_master WHERE sm_reg_no = v_reg_no LIMIT 1),
                                    'PHARM',
                                    v_reg_no,
                                    p_issue_date,
                                    p_certificate_type,
                                    1
                                );
                            END IF;
                        END LOOP;
                    END IF;

                ELSIF p_certificate_type = 'duplicate' THEN
                    -- If no issue date is provided, return error
                    IF p_issue_date IS NULL OR p_issue_date = '' THEN
                        RETURN jsonb_build_object(
                            'error', true,
                            'message', 'Issue date must be given'
                        );
                    END IF;

                    -- Insert duplicate records unconditionally
                    FOREACH v_reg_no IN ARRAY p_reg_numbers LOOP
                        INSERT INTO public.registration_certificate_issue (
                            sess_year,
                            reg_year,
                            inst_code,
                            dept_code,
                            reg_no,
                            reg_issued_on,
                            certificate_type,
                            is_download
                        ) VALUES (
                            p_sess_yr,
                            split_part(p_sess_yr, '-', 2),
                            (SELECT sm_inst_code FROM public.tbl_student_master WHERE sm_reg_no = v_reg_no LIMIT 1),
                            'PHARM',
                            v_reg_no,
                            p_issue_date,
                            p_certificate_type,
                            1
                        );
                    END LOOP;
                END IF;

                -- 2. Query student details (aliased to match original schema keys)
                SELECT jsonb_build_object(
                    'error', false,
                    'students', COALESCE(jsonb_agg(sub), '[]'::jsonb)
                )
                INTO v_result
                FROM (
                    SELECT DISTINCT ON (s.sm_reg_no)
                        s.sm_form_no AS s_appl_form_num,
                        s.sm_reg_no AS s_appl_reg_no,
                        s.sm_first_name AS s_first_name,
                        s.sm_middle_name AS s_middle_name,
                        s.sm_last_name AS s_last_name,
                        s.sm_photo AS s_photo,
                        s.sm_sign AS s_sign,
                        s.sm_inst_code AS s_inst_code,
                        i.i_code,
                        i.i_name,
                        s.sm_father_name AS s_father_name,
                        s.sm_session_year AS s_appl_sess_year,
                        rci.reg_issued_on
                    FROM public.tbl_student_master s
                    JOIN public.institute_master i ON i.i_code = s.sm_inst_code
                    JOIN public.registration_certificate_issue rci ON rci.reg_no = s.sm_reg_no
                    WHERE s.sm_reg_no = ANY(p_reg_numbers)
                      AND s.sm_session_year = p_sess_yr
                      AND rci.sess_year = p_sess_yr
                    ORDER BY s.sm_reg_no ASC, rci.id DESC
                ) sub;

                RETURN v_result;
            END;
            $$ LANGUAGE plpgsql;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP FUNCTION IF EXISTS public.fn_generate_registration_certificates(VARCHAR, VARCHAR[], VARCHAR, VARCHAR)");
    }
};
