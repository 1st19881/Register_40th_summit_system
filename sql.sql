CREATE TABLE draw_commands (
    cmd_id       NUMBER,
    cmd_type     VARCHAR2(50) DEFAULT 'SPIN',
    prize_name   VARCHAR2(500),
    status       VARCHAR2(20) DEFAULT 'PENDING',
    result_data  VARCHAR2(2000),
    created_at   DATE DEFAULT SYSDATE,
    processed_at DATE DEFAULT SYSDATE
);

CREATE SEQUENCE draw_cmd_seq START WITH 1 INCREMENT BY 1;
