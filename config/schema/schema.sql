CREATE
    TABLE users (
        id INT NOT NULL AUTO_INCREMENT,
        last_name VARCHAR(100) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        password VARCHAR(300) NOT NULL,
        mail VARCHAR(300) NOT NULL,
        created_at DATETIME,
        updated_at TIMESTAMP DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
        );

CREATE
    TABLE projects (
        id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        budget INT,
        customer_name VARCHAR(100),
        started_at DATE,
        finished_at DATE,
        created_at DATETIME,
        updated_at TIMESTAMP DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
        );

CREATE
    TABLE roles (
        id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        created_at DATETIME,
        updated_at TIMESTAMP DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
        );

CREATE
    TABLE projects_users (
        id INT NOT NULL AUTO_INCREMENT,
        project_id INT NOT NULL,
        user_id INT NOT NULL,
        role_id INT NOT NULL,
        FOREIGN KEY project_key(project_id) REFERENCES projects(id),
        FOREIGN KEY user_key(user_id) REFERENCES users(id),
        FOREIGN KEY role_key(role_id) REFERENCES roles(id),
        UNIQUE (project_id, user_id),
        PRIMARY KEY (id)
        );

CREATE
    TABLE minutes (
        id INT NOT NULL AUTO_INCREMENT,
        project_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        holded_place VARCHAR(255),
        holded_at DATETIME,
        created_at DATETIME,
        updated_at TIMESTAMP DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
        revision TINYINT,
        is_examined TINYINT(1),
        is_approved TINYINT(1),
        examined_at DATETIME,
        approved_at DATETIME,
        is_deleted TINYINT(1),
        FOREIGN KEY project_key(project_id) REFERENCES projects(id),
        PRIMARY KEY (id)
        );

CREATE
    TABLE participations (
        id INT NOT NULL AUTO_INCREMENT,
        projects_user_id INT NOT NULL,
        minute_id INT NOT NULL,
        is_participated TINYINT(1) NOT NULL,
        FOREIGN KEY projects_user_key(projects_user_id) REFERENCES projects_users(id),
        FOREIGN KEY minute_key(minute_id) REFERENCES minutes(id),
        UNIQUE (projects_user_id, minute_id),
        PRIMARY KEY (id)
        );

CREATE
    TABLE item_categories (
        id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(50) NOT NULL,
        created_at DATETIME,
        updated_at TIMESTAMP DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
        );

CREATE
    TABLE items (
        id INT NOT NULL AUTO_INCREMENT,
        minute_id INT NOT NULL,
        primary_no TINYINT,
        item_category_id INT NOT NULL,
        order_in_minute TINYINT,
        contents VARCHAR(300),
        revision TINYINT,
        overed_at DATE,
        created_at DATETIME,
        updated_at TIMESTAMP DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY minute_key(minute_id) REFERENCES minutes(id),
        FOREIGN KEY item_category_key(item_category_id) REFERENCES item_categories(id),
        PRIMARY KEY (id)
        );

CREATE
    TABLE responsibilities (
        id INT NOT NULL AUTO_INCREMENT,
        item_id INT NOT NULL,
        projects_user_id INT NOT NULL,
        FOREIGN KEY item_key(item_id) REFERENCES items(id),
        FOREIGN KEY projects_user_key(projects_user_id) REFERENCES projects_users(id),
        UNIQUE(item_id, projects_user_id),
        PRIMARY KEY (id)
        );