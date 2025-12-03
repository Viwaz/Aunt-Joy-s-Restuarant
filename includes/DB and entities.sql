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
RENAME TABLE meals TO menu_items;
alter table menu_items
-- drop column category;
add column category int,
add foreign key(category) references categories(id);


create table orders(
	order_id int auto_increment primary key,
    user_id int ,
    total_amount decimal(10,2),
    status enum('pending','en route','delivered') default 'pending',
    foreign key (user_id) references users(id)
);
alter table orders
add column order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

alter table orders
add column delivery_address varchar(100),
add column phone_num varchar(15);
create table order_itmes(
	id int AUTO_INCREMENT PRIMARY KEY,
    order_id int,
    menu_item_id int,
    quantity int,
    price decimal(10,2),
    FOREIGN KEY (order_id) references orders(order_id),
    FOREIGN KEY (menu_item_id) references menu_items(id)
);
rename table order_itmes to order_items;

create table cart(
	id INT AUTO_INCREMENT PRIMARY KEY,
    user_id int,
    menu_item_id int,
    quantity int,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);

create table categories(
	id int auto_increment primary key,
    category_name varchar(15)
);
INSERT INTO categories(category_name) VALUES
('Breakfast'),('Lunch'),('Dinner'),('Drinks');

replace menu_items(id,name,description,price_MWK,image,category) 
VALUES(1, 'Beef Fried Rice', 'Tastey bomb of flavours ready to explode',6000, 'Beef fried rice.jpg',2 );

select * from menu_items;
select * from users;
select * from categories;
select * from orders;
select * from order_items;
SELECT * FROM categories ORDER BY category_name;


