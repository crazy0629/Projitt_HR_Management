<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
</head>
<body>
<p>Please use this information to login</p>

Link <h4>{{ env('APP_URL') }}</h4>
User name <h4>{{$user->email}}</h4>
Password <h4>{{$user->temPass}}</h4>

</body>
</html>
