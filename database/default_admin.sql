-- Insert default admin account
-- Username: admin
-- Password: Admin@123

INSERT INTO `staffs` (`username`, `email`, `password`, `role`, `account_status`) 
VALUES (
    'admin',
    'admin@ktm.com',
    '$argon2id$v=19$m=65536,t=4,p=3$bHFLVE1fU0VDVVJFXzIwMjQ$JDJ5JDA0JEFkbWluQDEyM0tUTV9TRUNVUkVfMjAyNA',
    'admin',
    'active'
);
