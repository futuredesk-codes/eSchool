<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
</head>
<body>

@if( $type == 'application_accept')
    <h3>Hello {{ $name }},</h3>

    <p>Congratulations! Your child, {{ $child_name }}, has been successfully registered for the {{ $class_name }} class. Below are their login credentials:</p>

    <h4>Your Login Credentails:</h4>
    <p>
        <strong>Email</strong> {{ $username }}<br>
        <strong>Password:</strong> {{ $password }}<br>
    </p>

    <h4>Your Child's Login Credentials:</h4>
    <p>
        <strong>GR Number:</strong> {{ $child_grnumber }}<br>
        <strong>Password:</strong> {{ $child_password }}<br>
        <strong>Class Section:</strong> {{ $class_name }}
    </p>

    <p>If you encounter any issues or need further assistance, please do not hesitate to contact our support team.</p>

    <p>Thank you for being a part of our school {{$school_name}}.</p>

    Sincerely,
    <br>
    {{$school_name}}
    <br>
    {{ $school_contact}}
    <br>
    {{ $school_email }}

@endif

@if($type == 'application_reject')

    Dear {{ $name }},
    <br>
    <br>
    Thank you for your interest in {{ $school_name }}.
    <br>
    We have received {{ $child_name }}'s application for Class {{ $class_name }}. We appreciate the time and effort you invested in the application process.
    <br>
    After careful consideration, we regret to inform you that we are unable to offer {{ $child_name }} admission at this time. This decision was not made lightly, and it reflects the highly competitive nature of our admissions process this year.
    <br>
    We encourage {{ $child_name }} to continue pursuing their academic goals, and we wish them every success in their future endeavors.
    <br>
    Thank you once again for considering {{$school_name}}.
    <br>
    <br>
    <br>
    Sincerely,
    <br>
    {{$school_name}}
    <br>
    {{ $school_contact}}
    <br>
    {{ $school_email }}
@endif
</body>
</html>
