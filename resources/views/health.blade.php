<!DOCTYPE html>
<html>
<head>
    <title>Laravel Health Check</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 20px;
        }
        table {
            width: 80%;
            border-collapse: collapse;
            margin: auto;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: left;
        }
        th {
            background-color: #eee;
        }
    </style>
</head>
<body>
    <h2>🩺 Laravel Health Check</h2>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($health as $check)
                <tr>
                    <td>{{ $check['name'] }}</td>
                    <td>{{ $check['description'] }}</td>
                    <td>{{ $check['status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
