# Study Budy
A centralized, access-controlled educational web application.

## Setup Instructions

1. **Database Setup:**
   - Create a MySQL database (e.g., `study_budy_db` using phpMyAdmin).
   - Import the `database.sql` file into the new database to set up tables.
   
2. **Configuration:**
   - Edit `config/database.php` and verify the `$username`, `$password`, and `$db_name` variables match your MySQL setup. (Default for XAMPP is root with no password).

3. **Running the Application:**
   - Ensure the project folder (`study_budy_php`) is inside your `htdocs` or equivalent server root.
   - Start Apache and MySQL via XAMPP/MAMP/WAMP.
   - Navigate to `http://localhost/study_budy_php/` in your browser.

4. **Admin Access:**
   - Register a new account.
   - Open your MySQL database (via phpMyAdmin) and manually change the `role` of your user in the `users` table from `user` to `admin`.
   - Log in, and you will see the Admin Dashboard link.

## Features
- **Cream & Espresso Theme:** A highly decorative, visually appealing UI.
- **Upload Queue:** Documents are kept pending until approved by an admin.
- **Engagement:** Rate documents (1-5), bookmark them, and leave comments.
- **Robust Security:** PDO prepared statements, file size, and strict MIME type validations on backend.
