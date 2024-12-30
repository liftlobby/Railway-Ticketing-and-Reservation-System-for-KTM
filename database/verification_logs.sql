-- Create verification_logs table
CREATE TABLE IF NOT EXISTS verification_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    staff_id INT NOT NULL,
    verification_time DATETIME NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'onboard',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);
