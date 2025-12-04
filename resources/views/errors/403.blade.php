<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
  <title>{{ config('app.name') }}</title>
  <style>
    body {
      font-family: 'Geist', 'Roboto', 'Helvetica', 'Arial', 'sans-serif';
      background-color: #F3F4F6;
      height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      overflow: hidden;
    }

    .container {
      text-align: center;
    }

    h1 {
      margin-bottom: 0px;
      font-size: 1.25rem;
    }
    p {
margin: 0px;
    }

    .lock {
      border-radius: 5px;
      width: 55px;
      height: 45px;
      background-color: #333;
      animation: dip 1s;
      animation-delay: 1.5s;
      position: relative;
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    .lock::before,
    .lock::after {
      content: "";
      position: absolute;
      border-left: 5px solid #333;
      height: 20px;
      width: 30px;
      left: calc(50% - 20px);
    }

    .lock::before {
      top: -25px;
      border: 5px solid #333;
      border-bottom-color: transparent;
      border-radius: 15px 15px 0 0;
      height: 35px;
      animation: lock 2s ease-in-out, spin 2s ease-in-out;
    }

    .lock::after {
      top: -10px;
      border-right: 5px solid transparent;
      animation: spin 2s ease-in-out;
    }

    @keyframes lock {
      0% {
        top: -40px;
      }

      65% {
        top: -40px;
      }

      100% {
        top: -25px;
      }
    }

    @keyframes spin {
      0% {
        transform: scaleX(-1);
        left: calc(50% - 50px);
      }

      65% {
        transform: scaleX(1);
        left: calc(50% - 20px);
      }

      100% {
        transform: scaleX(1);
        left: calc(50% - 20px);
      }
    }

    @keyframes dip {
      0% {
        transform: translateY(0px);
      }

      50% {
        transform: translateY(10px);
      }

      100% {
        transform: translateY(0px);
      }
    }
  </style>
</head>

<body>
  <div class="lock"></div>
  <div class="container">
    <h1 class="text-xl">403 | Toegang tot deze pagina is beperkt</h1>
    <p>Neem contact op met de Helpdesk als je denkt dat dit een vergissing is.</p>
  </div>
</body>

</html>