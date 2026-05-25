-- Create pharmacy_appl_review_apply table for storing review applications

CREATE TABLE IF NOT EXISTS pharmacy_appl_review_apply (
    id SERIAL PRIMARY KEY,
    form_num VARCHAR(50),
    reg_no VARCHAR(50),
    roll_number VARCHAR(50),
    exam_year INTEGER,
    part_sem VARCHAR(50),
    paper_code TEXT,  -- JSON array of paper codes
    academic_session VARCHAR(20),
    review_status INTEGER DEFAULT 0,
    payment_transactions_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_review_apply_form_num ON pharmacy_appl_review_apply(form_num);
CREATE INDEX IF NOT EXISTS idx_review_apply_reg_no ON pharmacy_appl_review_apply(reg_no);
CREATE INDEX IF NOT EXISTS idx_review_apply_exam_year ON pharmacy_appl_review_apply(exam_year);
CREATE INDEX IF NOT EXISTS idx_review_apply_part_sem ON pharmacy_appl_review_apply(part_sem);
CREATE INDEX IF NOT EXISTS idx_review_apply_status ON pharmacy_appl_review_apply(review_status);
CREATE INDEX IF NOT EXISTS idx_review_apply_payment ON pharmacy_appl_review_apply(payment_transactions_id);

-- Create composite index for common queries
CREATE INDEX IF NOT EXISTS idx_review_apply_search ON pharmacy_appl_review_apply(part_sem, exam_year, review_status, payment_transactions_id);

COMMENT ON TABLE pharmacy_appl_review_apply IS 'Stores student review applications for pharmacy exams';
COMMENT ON COLUMN pharmacy_appl_review_apply.form_num IS 'Application form number';
COMMENT ON COLUMN pharmacy_appl_review_apply.reg_no IS 'Student registration number';
COMMENT ON COLUMN pharmacy_appl_review_apply.roll_number IS 'Student roll number';
COMMENT ON COLUMN pharmacy_appl_review_apply.exam_year IS 'Examination year';
COMMENT ON COLUMN pharmacy_appl_review_apply.part_sem IS 'Part/Semester (e.g., Part_I, Part_II)';
COMMENT ON COLUMN pharmacy_appl_review_apply.paper_code IS 'JSON array of paper codes for review';
COMMENT ON COLUMN pharmacy_appl_review_apply.academic_session IS 'Academic session (e.g., 2025-2026)';
COMMENT ON COLUMN pharmacy_appl_review_apply.review_status IS 'Review status: 0=pending, 1=applied, 2=paid';
COMMENT ON COLUMN pharmacy_appl_review_apply.payment_transactions_id IS 'Reference to payment transaction';
