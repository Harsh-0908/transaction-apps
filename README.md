# Transaction App

## Database Setup

Run the following SQL to create the required tables in your MySQL database (`app_transaction`):

```sql
CREATE TABLE `User` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `firstname` VARCHAR(100) NOT NULL,
    `lastname` VARCHAR(100) NOT NULL,
    `introduction` TEXT,
    `deposit` DECIMAL(12,2) NOT NULL,
    `total_confirmed_amount` DECIMAL(12,2) NOT NULL,
    `currency` ENUM('INR', 'USD', 'AUD') NOT NULL,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `due_date` DATE NOT NULL
);

CREATE TABLE `Transaction` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `currency` ENUM('INR', 'USD', 'AUD') NOT NULL,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `paymethod` ENUM('cash', 'cheque', 'online') NOT NULL,
    `datetime` DATETIME NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `User`(`id`)
);
```

---

- Make sure your database name is `app_transaction` (or update `config/db.php` accordingly).
- Import the above SQL using phpMyAdmin or the MySQL command line. 