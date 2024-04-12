<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Session;

class GameController extends Controller
{
    const MIN_DICE_VALUE = 1;
    const MAX_DICE_VALUE = 6;
    const BET_AMOUNT = 10;
    const WIN_AMOUNT_BELOW_7 = 20;
    const WIN_AMOUNT_ABOVE_7 = 20;
    const WIN_AMOUNT_LUCKY_7 = 30;

    public function index()
    {
        if (!Session::has("user_wallet")) {
            Session::put("user_wallet", 100);
        }
        return view("lucky");
    }

    public function submit(Request $request)
    {
        $userInput = $request->place_btn;
        $dice1 = rand(self::MIN_DICE_VALUE, self::MAX_DICE_VALUE);
        $dice2 = rand(self::MIN_DICE_VALUE, self::MAX_DICE_VALUE);
        $sum = $dice1 + $dice2;
        $currentBalance = Session::get("user_wallet");

        if ($userInput == "below_7") {
            $winBalance = $sum < 7 ? self::WIN_AMOUNT_BELOW_7 : 0;
        } elseif ($userInput == "above_7") {
            $winBalance = $sum > 7 ? self::WIN_AMOUNT_ABOVE_7 : 0;
        } elseif ($userInput == "lucky_7") {
            $winBalance = $sum == 7 ? self::WIN_AMOUNT_LUCKY_7 : 0;
        } else {
            // Handle invalid input
            return redirect('/')->with('error', 'Invalid input.');
        }

        $currentBalance = $currentBalance + $winBalance - self::BET_AMOUNT;
        Session::put("user_wallet", $currentBalance);

        $diceData = [
            'dice1' => $dice1,
            'dice2' => $dice2,
        ];
        Session::put("diceData", $diceData);
        return redirect('/');
    }

    public function reset()
    {
        Session::forget("diceData");
        Session::put("user_wallet", 100);
        return redirect("/");
    }
}
