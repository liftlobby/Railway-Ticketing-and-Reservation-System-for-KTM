<!-- Ticketing & Reservation Page -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM Ticketing & Reservation</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        header {
            background-color: #0078D7;
            color: white;
            padding: 15px;
            text-align: center;
        }

        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .button {
            display: inline-block;
            margin: 10px 0;
            padding: 10px 20px;
            background-color: #0078D7;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }

        .button:hover {
            background-color: #005bb5;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th, .table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .table th {
            background-color: #0078D7;
            color: white;
        }
    </style>
</head>
<body>
    <header>
        <h1>KTM Ticketing & Reservation System</h1>
    </header>

    <div class="container">
        <h2>Available Tickets & Reservation</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Train</th>
                    <th>Route</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Express 101</td>
                    <td>Kuala Lumpur - Johor Bahru</td>
                    <td>2024-12-20</td>
                    <td>10:00 AM</td>
                    <td>RM 60</td>
                    <td><button class="button">Reserve</button></td>
                </tr>
                <tr>
                    <td>Express 202</td>
                    <td>Ipoh - Penang</td>
                    <td>2024-12-21</td>
                    <td>1:30 PM</td>
                    <td>RM 40</td>
                    <td><button class="button">Reserve</button></td>
                </tr>
                <tr>
                    <td>Express 303</td>
                    <td>Seremban - Melaka</td>
                    <td>2024-12-22</td>
                    <td>8:00 AM</td>
                    <td>RM 30</td>
                    <td><button class="button">Reserve</button></td>
                </tr>
                <tr>
                    <td>Express 404</td>
                    <td>Johor Bahru - Singapore</td>
                    <td>2024-12-23</td>
                    <td>3:00 PM</td>
                    <td>RM 20</td>
                    <td><button class="button">Reserve</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>

<!-- Admin Edit Page -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Edit Tickets</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        header {
            background-color: #0078D7;
            color: white;
            padding: 15px;
            text-align: center;
        }

        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        form label {
            display: block;
            margin: 10px 0 5px;
        }

        form input, form select, form textarea {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .admin-options {
            margin-top: 20px;
        }

        .button {
            display: inline-block;
            margin: 10px 0;
            padding: 10px 20px;
            background-color: #0078D7;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }

        .button:hover {
            background-color: #005bb5;
        }

        .button.delete {
            background-color: red;
        }

        .button.delete:hover {
            background-color: darkred;
        }
    </style>
</head>
<body>
    <header>
        <h1>Admin - Edit Ticket Information</h1>
    </header>

    <div class="container">
        <form>
            <label for="train">Train</label>
            <input type="text" id="train" name="train" placeholder="Enter train name">

            <label for="route">Route</label>
            <input type="text" id="route" name="route" placeholder="Enter route">

            <label for="date">Date</label>
            <input type="date" id="date" name="date">

            <label for="time">Time</label>
            <input type="time" id="time" name="time">

            <label for="price">Price</label>
            <input type="number" id="price" name="price" placeholder="Enter price">

            <div class="admin-options">
                <button class="button" type="submit">Save Changes</button>
                <button class="button delete" type="button">Delete</button>
            </div>
        </form>
    </div>
</body>
</html>
