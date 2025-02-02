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
    html,
    body {
      font-family: 'Inter', 'Roboto', 'Helvetica', 'Arial', 'sans-serif';
      background-color: #F3F4F6;
    }

    html {
      box-sizing: border-box;
      -webkit-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
    }

    *,
    *:before,
    *:after {
      box-sizing: inherit;
    }

    .container {
      width: 100%;
    }

    .title-center-xy {
      top: 440px;
      left: 50%;
      transform: translate(-50%, -50%);
      position: absolute;
    }

    .center-xy {
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      position: absolute;
    }

    .copy-container {
      text-align: center;
    }

    p {
      font-size: 1.25rem;
      text-transform: uppercase;
      letter-spacing: 0.2px;
      margin: 0px;
    }

    .handle {
      background: #008F92;
      width: 3px;
      height: 25px;
      top: -1px;
      left: 0;
      margin-top: 1px;
      position: absolute;
      animation: blink 1.2s infinite, moveX 1s linear forwards;
      animation-delay: 1s, 0s;
    }

    #cb-replay {
      fill: #666;
      width: 20px;
      margin: 15px;
      right: 0;
      bottom: 0;
      position: absolute;
      overflow: inherit;
      cursor: pointer;
    }

    #cb-replay:hover {
      fill: #888;
    }

    @keyframes blink {
      0% {
        opacity: 0;
      }

      75% {
        opacity: 0;
      }

      99% {
        opacity: 1;
      }

      100% {
        opacity: 0;
      }
    }

    @keyframes moveX {
      0% {
        transform: translateX(0px);
      }

      100% {
        transform: translateX(290px);
      }
    }

    @keyframes showLetter {
      0% {
        opacity: 0;
      }

      100% {
        opacity: 1;
      }
    }

    .letter {
      display: inline-block;
      opacity: 0;
      transform: scale(1);
    }

    .letter.visible {
      animation: showLetter 0.1s;
      opacity: 1;
    }
  </style>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Select elements
      var copyContainer = document.querySelector(".copy-container");
      var replayIcon = document.getElementById("cb-replay");
      var copyWidth = copyContainer.querySelector("p").offsetWidth;

      // Split text into letters
      var textContent = copyContainer.querySelector("p").textContent;
      copyContainer.querySelector("p").innerHTML = textContent.split("").map(function(char) {
        if (char === " ") {
          return `<span class="letter">&nbsp;</span>`;
        }
        return `<span class="letter">${char}</span>`;
      }).join("");

      var letterElements = copyContainer.querySelectorAll(".letter");

      // Animate the letters
      function animateCopy() {
        letterElements.forEach(function(letter, index) {
          setTimeout(function() {
            letter.classList.add("visible");
            // animateHandle(index);
          }, index * 37); // Delay each letter by 100ms
        });
      }

      // Animate the handle
      function animateHandle(index) {
        var handle = document.querySelector(".handle");
        var position = (index + 1) * (copyWidth / letterElements.length); // Calculate handle position
        handle.style.transition = "transform 0.1s steps(6)";
        handle.style.transform = `translateX(${position}px)`;
        handle.style.opacity = 1;
      }

      // Start the initial animation
      animateCopy();
    });
  </script>
</head>

<body class="h-screen flex flex-col justify-center items-center">
  <div class="container">
    <!-- <div class="title-center-xy">
      <h1>AZ MONICA</h1>
    </div> -->
    <div class="copy-container center-xy">

      <p>404 | PAGINA NIET GEVONDEN.</p>
      <span class="handle"></span>

    </div>
  </div>


  <svg version="1.1" id="cb-replay" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
    viewBox="0 0 279.9 297.3" style="enable-background:new 0 0 279.9 297.3;" xml:space="preserve">
    <g>
      <path d="M269.4,162.6c-2.7,66.5-55.6,120.1-121.8,123.9c-77,4.4-141.3-60-136.8-136.9C14.7,81.7,71,27.8,140,27.8
		c1.8,0,3.5,0,5.3,0.1c0.3,0,0.5,0.2,0.5,0.5v15c0,1.5,1.6,2.4,2.9,1.7l35.9-20.7c1.3-0.7,1.3-2.6,0-3.3L148.6,0.3
		c-1.3-0.7-2.9,0.2-2.9,1.7v15c0,0.3-0.2,0.5-0.5,0.5c-1.7-0.1-3.5-0.1-5.2-0.1C63.3,17.3,1,78.9,0,155.4
		C-1,233.8,63.4,298.3,141.9,297.3c74.6-1,135.1-60.2,138-134.3c0.1-3-2.3-5.4-5.3-5.4l0,0C271.8,157.6,269.5,159.8,269.4,162.6z" />
    </g>
  </svg>


</body>

</html>