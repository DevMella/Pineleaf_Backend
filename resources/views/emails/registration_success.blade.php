<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
    <link rel="stylesheet" href="pine.css" />
  </head>
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
  <body>
    <div class="bo">
      <div class="all">
        <div class="top">
          <img src="img/logo 13.png" alt="" />
        </div>
        <div class="white">
          <div class="text">
            <h1>Registration Welcome Email</h1>
          </div>
          <div class="botext">
            <span>Hi {{ $firstName }},</span>
            <p>
              Thank you for registering with Pineleaf Estate, we’re truly
              excited to have you join our growing family of smart investors and
              dream builders. At Pineleaf, we believe land is more than just
              property — it’s the foundation for legacy, peace of mind, and
              future wealth. That’s why we’re committed to making land ownership
              secure, accessible, and stress-free for people like you. You’ve
              taken the first step toward something meaningful — and we’re here
              to walk this journey with you. Whether you're investing for the
              future, planning a home, or securing something for your loved
              ones, we’ll be right here with verified properties, transparent
              processes, and real support. Welcome again — your journey to
              lasting value starts now. Need help or have questions? Our team is
              ready to assist anytime: info@pineleafestate.com Warm regards, The
              Pineleaf Estate Team Where every property is a smart investment.
            </p>
          </div>
        </div>
        <div class="foot">
            <h4>Follow Us</h4>
            <div class="imgg">
                <div>
                    <img src="img/Symbol.svg.png" alt="">
                </div>
                <div>
                    <img src="img/Symbol Alternative.svg.png" alt="">
                </div>
                <div>
                    <img src="img/Symbol Alternative.svg (1).png" alt="">
                </div>
                
            </div>
        </div>
      </div>
    </div>
  </body>
</html>
