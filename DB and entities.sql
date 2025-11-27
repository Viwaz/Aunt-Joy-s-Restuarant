CREATE database Aunt_JoyDB;
use aunt_joydb;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin', 'sales', 'manager') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price_MWK DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    category SET('Breakfast','Lunch','Dinner','Drinks'),
    availability ENUM('in_stock','out_of_stock') DEFAULT 'in_stock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO meals(id,name,description,price_MWK,image,category) 
VALUES(1, 'Beef Fried Rice', 'Tastey bomb of flavours ready to explode',6000, 'Beef fried rice.jpg', 'Lunch, Supper');


select * from meals;
select * from users;
drop table meals;

