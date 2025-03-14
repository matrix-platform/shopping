
CREATE TABLE base_payment_method (
    id           INTEGER   NOT NULL PRIMARY KEY,
    title        TEXT          NULL,
    description  TEXT          NULL,
    enable_time  TIMESTAMP     NULL,
    disable_time TIMESTAMP     NULL,
    ranking      INTEGER   NOT NULL
);

CREATE TABLE common_order (
    id                INTEGER          NOT NULL PRIMARY KEY,
    order_no          TEXT             NOT NULL UNIQUE,
    member_id         INTEGER              NULL,
    amount            DOUBLE PRECISION NOT NULL,
    shipping          DOUBLE PRECISION NOT NULL,
    --
    payment_method_id INTEGER          NOT NULL,
    payment           TEXT                 NULL,
    payment_request   TEXT                 NULL,
    payment_response  TEXT                 NULL,
    payment_notice    TEXT                 NULL,
    payment_ver       INTEGER          NOT NULL,
    pay_time          TIMESTAMP            NULL,
    drawback_time     TIMESTAMP            NULL,
    --
    invoice_num       TEXT                 NULL,
    invoice_type      INTEGER              NULL, -- options: invoice-type
    invoice_category  INTEGER              NULL, -- options: invoice-category
    tax_id            TEXT                 NULL,
    invoice_title     TEXT                 NULL,
    invoice_request   TEXT                 NULL,
    invoice_response  TEXT                 NULL,
    invoice_time      TIMESTAMP            NULL,
    invoice_ver       INTEGER              NULL,
    --
    snapshot          TEXT                 NULL,
    remark            TEXT                 NULL,
    create_time       TIMESTAMP        NOT NULL,
    cancel_time       TIMESTAMP            NULL,
    status            INTEGER          NOT NULL  -- options: order-status
);

CREATE TABLE base_order () INHERITS (common_order);

