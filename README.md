# Orderly - Restaurant Management System

Orderly is a web-based restaurant management system designed to streamline the interaction between managers, kitchen staff, and customers (tables). It provides distinct dashboards for each role, facilitating real-time order processing and menu management.

## 🚀 Features

### 1. **Manager Dashboard (`dashboards/manager.php`)**
*   **Menu Management:** Add, edit, and delete dishes (`alimenti`) and categories (`categorie`).
*   **Detailed Dish Info:** Set names, descriptions, prices, allergens (with checkboxes), and upload images.
*   **Category Organization:** Create and manage menu categories (e.g., Starters, Mains, Desserts).
*   **Theme Toggle:** Switch between light and dark modes.

### 2. **Kitchen Dashboard (`dashboards/cucina.php`)**
*   **Kanban Board:** Visualizes orders in real-time with columns for **In Arrivo** (Incoming) and **In Preparazione** (In Preparation).
*   **Status Management:** Cooks can move orders from "Incoming" to "Preparation" and then mark them as "Ready".
*   **Sticky Headers:** Column headers remain visible while scrolling through long lists of orders.
*   **Timers:** Displays the time elapsed since an order was placed, highlighting delayed orders.
*   **Sound Notifications:** Plays a sound when a new order arrives.
*   **Theme Toggle:** Switch between light and dark modes.

### 3. **Table/Customer Dashboard (`dashboards/tavolo.php`)**
*   **Digital Menu:** Browse the full menu filtered by category.
*   **Search & Filter:** Search for dishes by name or filter out allergens.
*   **Interactive Cart:** Add items to a client-side cart, adjust quantities, and view the total price.
*   **Order Submission:** Submit the cart directly to the kitchen.
*   **Order History:** View past orders for the current session.
*   **Product Zoom:** View detailed information and larger images of dishes in a modal.

## 🛠 Technical Stack

*   **Backend:** PHP (Native), MySQL
*   **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
*   **Styling:** Bootstrap 5.3.3, Custom CSS (Variables for theming), FontAwesome 6.4.0, Google Fonts (Poppins)
*   **Architecture:** Multi-role session-based authentication.

## 📂 Project Structure

*   `api/`: PHP scripts handling AJAX requests (e.g., adding dishes, submitting orders, fetching kitchen data).
*   `css/`: Custom stylesheets for each dashboard (`manager.css`, `cucina.css`, `tavolo.css`) and common styles (`common.css`).
*   `dashboards/`: Main PHP files for the user interfaces.
*   `imgs/`: Images for dishes and the application logo.
*   `include/`: Shared PHP files (database connection, header, footer).
*   `js/`: JavaScript logic for each dashboard (e.g., `tavolo.js` for cart logic).
*   `index.php`: Login page handling authentication and role redirection.

## ⚙️ Installation & Setup

1.  **Database Setup:**
    *   Import the provided SQL dump (e.g., `templatedb.sql`) into your MySQL database.
    *   Configure the database connection in `include/conn.php`:
        ```php
        $conn = mysqli_connect("localhost", "root", "", "ristorante_db");
        ```

2.  **Server Requirements:**
    *   PHP 7.4 or higher.
    *   MySQL/MariaDB server.
    *   Web server (Apache/Nginx).

3.  **Login Credentials (Default):**
    *   **Manager:** `admin` / `admin` (Example)
    *   **Kitchen:** `cuoco` / `cuoco` (Example)
    *   **Table:** `tavolo1` / `password` (Example - varies by table setup)

## 📝 Usage

1.  **Login:** Access `index.php` and log in with your role's credentials.
2.  **Manager:** Use the dashboard to set up the menu.
3.  **Table:** A customer logs in (or scans a QR code leading to the login), browses the menu, and places an order.
4.  **Kitchen:** The kitchen staff sees the new order appear on the Kanban board, starts preparation, and marks it as ready when done.

## 🎨 Theming

The application supports a **Dark Mode** which can be toggled via the moon/sun icon in the header of any dashboard. The preference is saved in `localStorage`.
