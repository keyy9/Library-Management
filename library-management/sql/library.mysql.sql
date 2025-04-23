-- Create database if not exists
CREATE DATABASE IF NOT EXISTS library_management;
USE library_management;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Authors table
CREATE TABLE IF NOT EXISTS authors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Books table
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author_id INT NOT NULL,
    category_id INT NOT NULL,
    isbn VARCHAR(13) NOT NULL UNIQUE,
    published_year INT,
    publisher VARCHAR(100),
    description TEXT,
    cover_image VARCHAR(255),
    total_copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES authors(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

-- Borrowings table
CREATE TABLE IF NOT EXISTS borrowings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE,
    status ENUM('borrowed', 'returned', 'overdue') NOT NULL DEFAULT 'borrowed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
) ENGINE=InnoDB;

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@library.com', 'admin');

-- Insert sample categories
INSERT INTO categories (name, description) VALUES
('Fiction', 'Fictional literature and novels'),
('Non-Fiction', 'Educational and factual books'),
('Science', 'Scientific books and research'),
('Technology', 'Books about technology and computing'),
('History', 'Historical books and documentation');

-- Insert sample authors
INSERT INTO authors (name, bio) VALUES
('John Smith', 'Bestselling author of fiction novels'),
('Sarah Johnson', 'Expert in technology and computing'),
('Michael Brown', 'Historical researcher and author'),
('Emily Wilson', 'Science writer and researcher'),
('David Miller', 'Non-fiction author and educator');

-- Insert sample books
INSERT INTO books (title, author_id, category_id, isbn, published_year, publisher, description, cover_image, total_copies, available_copies) VALUES
('The Art of Programming', 2, 4, '9781234567890', 2022, 'Tech Publishing', 'A comprehensive guide to programming', 'https://images.pexels.com/photos/2465877/pexels-photo-2465877.jpeg', 3, 3),
('World History: A Journey', 3, 5, '9780987654321', 2021, 'History Books', 'Exploring world history through ages', 'https://images.pexels.com/photos/2128249/pexels-photo-2128249.jpeg', 2, 2),
('The Mystery of Time', 1, 1, '9785432109876', 2023, 'Fiction House', 'A thrilling mystery novel', 'https://images.pexels.com/photos/1765033/pexels-photo-1765033.jpeg', 4, 4),
('Science Today', 4, 3, '9789876543210', 2022, 'Science Press', 'Modern scientific discoveries', 'https://images.pexels.com/photos/2280547/pexels-photo-2280547.jpeg', 3, 3),
('Life Skills', 5, 2, '9784567890123', 2023, 'Education Plus', 'Essential life skills guide', 'https://images.pexels.com/photos/1925536/pexels-photo-1925536.jpeg', 2, 2);
