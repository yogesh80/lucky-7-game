<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game</title>
    <style>
        .card {
            width: 100%;
            background: black;
            margin: auto;
            min-height: 500px;
            color: purple;
        }
        .row{
          display: flex;
          padding: auto;
          margin: auto;
        }
        .submitbtn{
            width: 200px;
            height: 30px;
            color: white;
            background-color: purple;
            border: none;
            margin-left: 30px;
            cursor: pointer;
        }
        .place_btn{
            cursor: pointer;
            width: 60px;
            height: 60px;
            list-style: none;
        }
        .bet__line{
            font-size: 25px;
            font-weight: 300;
            padding-right: 30px;
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>Welcome to lucky 7</h1>
        <form method="post" action="{{url("/submit-bet")}}">
            @csrf
            <div class="row">
                <h2 class="bet__line">@if(Session::get("user_wallet") < 10 ) You do not have required blance  @else Place your bet (Rs 10) @endif</h2>
                <input type="radio" required class="btn" name="place_btn" value="below_7">Below 7</button>
                <input type="radio" required="btn" name="place_btn" value="lucky_7">lucky 7</button>
                <input type="radio" required ="btn" name="place_btn" value="above_7">Above 7</button>
                <button type="submit" class="submitbtn" @if(Session::get("user_wallet") < 10) disabled style="opacity: 0.3"  @endif>Play</button>
            </div>

        <h3>Game Result</h3>

       <p> Dice 1: {{Session::get("diceData.dice1") ?? ''}}  </p>
       <p> Dice 1: {{Session::get("diceData.dice2") ?? ''}} </p>
       <p> Your Blance is :  {{Session::get("user_wallet") ?? 100}}  </p>
       <button type="button" class="submitbtn" onclick="window.location.href='{{url('/reset')}}'">Reset and Play Again</button>
       <button type="submit" @if(Session::get("user_wallet") < 10) disabled style="opacity: 0.3" @endif class="submitbtn" >Continue Playing</button>
      </form>

    </div>
</body>

</html>