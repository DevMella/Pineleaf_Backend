<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Password Reset</title>
  <link rel="stylesheet" href="{{ asset('pine.css') }}" />
  <style>
    *{
    padding: 0;
    margin: 0;
    box-sizing: border-box;
}
.bo{
    display: flex;
    justify-content: center;
}
.all{
    background-color: #18531D80;
    height: 100vh;
}
.top{
    text-align: center;
    padding: 20px 0;
    height: 150px;
}
.top img{
    width: 130px;
}
.white{
    background-color: white;
    height: 450px;
    align-items: center;
    padding: 0px 0px 20px 0px;
}
.text{
    display: flex;
    justify-content: center;
    padding: 20px 0;
}
.text h1{
    color: #2F5318;
}
.botext{
    padding: 10px 30px 0px 30px;
}
.botext span{
    font-weight: 800;
}
.botext p{
    padding-top: 10px;
    line-height: 22.4px;
}
.foot{
    text-align: center;
    height: auto;
}
.foot h4{
    padding: 15px 0px;
    color: #fff;
    font-weight: 600;
}
.imgg{
    display: flex;
    text-align: center;
    justify-content: center;
    gap: 10px;
}

@media (min-width: 640px) and (max-width: 1023px) {
  /* Styles for medium screens */
  .white p{
    text-align: start;
    font-size: 20px;
    line-height: 40px;
  }
  .white{
    height: 700px;
  }
}

@media (max-width: 639px) {
  /* Styles for small screens */
    .white{
       height: 500px;
       padding: 0px;
    }
    .top{
        height: 150px;
        padding: 0px;
    }
    .botext{
        padding: 10px;
    }
    .white p{
    text-align: start;
    font-size: 18px;
    line-height: 20px;
  }
}
  </style>
</head>
<body>
  <div class="bo">
    <div class="all">
      <div class="top">
        <img src="https://pineleafestates.com/views/index_images/logo.png" alt="" />
      </div>
      <div class="white">
        <div class="text">
          <h1>Password Reset</h1>
        </div>
        <div class="botext">
          <span>Hi {{ $fullName }},</span>
          <p>
            We received a request to reset your password for your Pineleaf Estate account.
            If you made this request, you can set a new password using the link below:
            <a href="{{ $resetUrl }}">Reset Password</a>.
            This link will expire in 30 minutes for your security. If it expires, you can always request a new one.
            If you didn’t request a password reset, please ignore this message. Your account remains safe and secure.
            <br><br>
            Need help or have questions? Reach out to us at support@pineleafestate.com.
            <br><br>
            With care, <br>
            The Pineleaf Estate Team
          </p>
        </div>
      </div>
      <div class="foot">
        <h4>Follow Us</h4>
        <div class="imgg">
          <div><img src="{{ asset('img/Symbol.svg.png') }}" alt="" /></div>
          <div><img src="{{ asset('img/Symbol Alternative.svg.png') }}" alt="" /></div>
          <div><img src="{{ asset('img/Symbol Alternative.svg (1).png') }}" alt="" /></div>
        </div>
      </div>
      <div class="foot">
        <h4>Follow Us</h4>
        <div class="imgg">
            <div><img src="{{ asset('img/Symbol Alternative.svg') }}" alt=""></div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
