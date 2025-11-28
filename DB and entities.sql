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
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status enum('pending','en route','delivered') default 'pending',
    foreign key (user_id) references users(id)
);
alter table orders
add column order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
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

Select cat.category_name as category
from menu_items m
join categories cat 
on m.category = cat.id;

select cat.id from categories cat where cat.category_name = 'Lunch';

select * from menu_items;
select * from users;
select * from categories;
select * from orders;
select * from order_items;
SELECT 
		m.name as meal_name,
		SUM(oi.quantity) as total_quantity,
		SUM(oi.quantity * oi.price) as total_revenue
	FROM order_items oi
		JOIN menu_items m ON oi.menu_item_id = m.id
		JOIN orders o ON oi.order_id = o.order_id
		WHERE DATE(o.order_date) BETWEEN '2025-11-1' AND '2025-11-20'
		AND o.status = 'Delivered'
		GROUP BY m.id, m.name
		ORDER BY total_quantity DESC
		LIMIT 10;
        
SELECT * FROM categories ORDER BY category_name;


