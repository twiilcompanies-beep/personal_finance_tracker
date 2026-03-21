-- ============================================================
-- FinPulse Personal Finance Tracker — Database Setup Script
-- Run this once in phpMyAdmin or MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS finpulse_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE finpulse_db;

-- ------------------------------------------------------------
-- Table: users
-- Stores registered user accounts
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT(11)      NOT NULL AUTO_INCREMENT,
    username    VARCHAR(100) NOT NULL,
    email       VARCHAR(191) NOT NULL,
    password    VARCHAR(255) NOT NULL,   -- bcrypt hash via password_hash()
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: messages
-- Stores contact-form submissions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS messages (
    id          INT(11)      NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(191) NOT NULL,
    message     TEXT         NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: expenses
-- Stores income and expense transactions per user
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS expenses (
    id          INT(11)        NOT NULL AUTO_INCREMENT,
    user_id     INT(11)        NOT NULL,
    amount      DECIMAL(10,2)  NOT NULL,
    category    VARCHAR(100)   NOT NULL,
    description VARCHAR(255)   NOT NULL DEFAULT '',
    type        ENUM('expense','income') NOT NULL DEFAULT 'expense',
    date        DATE           NOT NULL,
    created_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_expenses_user_id (user_id),
    KEY idx_expenses_date    (date),
    CONSTRAINT fk_expenses_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
