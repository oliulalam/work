<?php
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://diuvin.shop';
header("Access-Control-Allow-Origin: " . $origin);
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set('Asia/Kolkata');
$serviceNowTimeFormatted = date('Y-m-d H:i:s');

$jsonData = '{
    "data":  {
                 "popular":  {
                                 "platformList":  [
                                                      {
                                                          "vendorId":  87,
                                                          "vendorCode":  "INOUT",
                                                          "gameCode":  "2126c5c458316ba1f2df65b387b60408",
                                                          "gameNameEn":  "Chicken Road",
                                                          "imgUrl":  "https://luckmedia.link/iog_chicken_road/thumb_3_4_custom.webp",
                                                          "imgUrl2":  "",
                                                          "winOdds":  96.42,
                                                          "gameID":  "2126c5c458316ba1f2df65b387b60408"
                                                      },
                                                       {
                                                           "vendorId":  87,
                                                           "vendorCode":  "INOUT",
                                                           "gameCode":  "562b299961b0ec40f252a832453c67b0",
                                                           "gameNameEn":  "Cricket",
                                                           "imgUrl":  "https://luckmedia.link/iog_chicken_road_2/thumb_3_4_custom.webp",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.58,
                                                           "gameID":  "562b299961b0ec40f252a832453c67b0"
                                                       },
                                                       {
                                                           "vendorId":  23,
                                                           "vendorCode":  "TB_Chess",
                                                           "gameCode":  "a04d1f3eb8ccec8a4823bdf18e3f0e84",
                                                           "gameNameEn":  "Aviator",
                                                           "imgUrl":  "https://ossimg.forpicstore777.top/rajalottery/gamelogo/TB_Chess/800_20250421220338955.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  97.57,
                                                           "gameID":  "a04d1f3eb8ccec8a4823bdf18e3f0e84"
                                                       },
                                                       {
                                                           "vendorId":  5,
                                                           "vendorCode":  "PG",
                                                           "gameCode":  "f08cc025e23ee049b570517867c74be0",
                                                           "gameNameEn":  "Pinata Wins",
                                                           "imgUrl":  "https://365paisa1.com/PG/pg/s10.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.15,
                                                           "gameID":  "f08cc025e23ee049b570517867c74be0"
                                                       },
                                                       {
                                                           "vendorId":  5,
                                                           "vendorCode":  "PG",
                                                           "gameCode":  "95fc290bb05c07b5aad1a054eba4dcc4",
                                                           "gameNameEn":  "Wild Bandito",
                                                           "imgUrl":  "https://365paisa1.com/PG/pg/s8.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.93,
                                                           "gameID":  "95fc290bb05c07b5aad1a054eba4dcc4"
                                                       },
                                                       {
                                                           "vendorId":  5,
                                                           "vendorCode":  "PG",
                                                           "gameCode":  "2818a7add6e10b2ec5f938d7ae0efb04",
                                                           "gameNameEn":  "Buffalo Win",
                                                           "imgUrl":  "https://ossimg.91admin123admin.com/91club/gamelogo/PG/108.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.74,
                                                           "gameID":  "2818a7add6e10b2ec5f938d7ae0efb04"
                                                       },
                                                       {
                                                           "vendorId":  5,
                                                           "vendorCode":  "PG",
                                                           "gameCode":  "1189baca156e1bbbecc3b26651a63565",
                                                           "gameNameEn":  "Mahjong Ways",
                                                           "imgUrl":  "https://ossimg.91admin123admin.com/91club/gamelogo/PG/65.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.92,
                                                           "gameID":  "1189baca156e1bbbecc3b26651a63565"
                                                       }
                                                   ],
                                  "clicksTopList":  [
                                                        {
                                                            "vendorId":  37,
                                                            "vendorCode":  "JILI",
                                                            "gameCode":  "bdfb23c974a2517198c5443adeea77a8",
                                                            "gameNameEn":  "Super Ace",
                                                            "imgUrl":  "https://365paisa1.com/jili/jili/s2.png",
                                                            "imgUrl2":  "",
                                                            "winOdds":  96.19,
                                                            "gameID":  "bdfb23c974a2517198c5443adeea77a8"
                                                        },
                                                        {
                                                            "vendorId":  37,
                                                            "vendorCode":  "JILI",
                                                            "gameCode":  "63927e939636f45e9d6d0b3717b3b1c1",
                                                            "gameNameEn":  "Fortune Gems 3",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s1.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.44,
                                                           "gameID":  "63927e939636f45e9d6d0b3717b3b1c1"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "ddfbe3b51c60ab9166310916a43cb17f",
                                                           "gameNameEn":  "Fortune Garuda 500",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s6.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  97.24,
                                                           "gameID":  "ddfbe3b51c60ab9166310916a43cb17f"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "1ab23c1fda76ec10778b458fac552e37",
                                                           "gameNameEn":  "Circus Joker 4096",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s3.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.11,
                                                           "gameID":  "1ab23c1fda76ec10778b458fac552e37"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "981f5f9675002fbeaaf24c4128b938d7",
                                                           "gameNameEn":  "Boxing King",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s4.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  97.06,
                                                           "gameID":  "981f5f9675002fbeaaf24c4128b938d7"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "80aad2a10ae6a95068b50160d6c78897",
                                                           "gameNameEn":  "Super Ace Deluxe",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s5.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  97.69,
                                                           "gameID":  "80aad2a10ae6a95068b50160d6c78897"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "083a2fbb35612d3f7925acedece5904f",
                                                           "gameNameEn":  "Super Ace II",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s9.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.14,
                                                           "gameID":  "083a2fbb35612d3f7925acedece5904f"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "db249defce63610fccabfa829a405232",
                                                           "gameNameEn":  "Money Coming",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s7.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.75,
                                                           "gameID":  "db249defce63610fccabfa829a405232"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "664fba4da609ee82b78820b1f570f4ad",
                                                           "gameNameEn":  "Fortune Gems 2",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s8.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  97.52,
                                                           "gameID":  "664fba4da609ee82b78820b1f570f4ad"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "490096198e28f770a3f85adb6ee49e0f",
                                                           "gameNameEn":  "Golden Empire",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s12.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  97.58,
                                                           "gameID":  "490096198e28f770a3f85adb6ee49e0f"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "a990de177577a2e6a889aaac5f57b429",
                                                           "gameNameEn":  "Fortune Gems",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s10.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  97.84,
                                                           "gameID":  "a990de177577a2e6a889aaac5f57b429"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "731e642b1fee94725e7313f3dfba8f45",
                                                           "gameNameEn":  "Lucky Jaguar",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s16.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.35,
                                                           "gameID":  "731e642b1fee94725e7313f3dfba8f45"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "8c62471fd4e28c084a61811a3958f7a1",
                                                           "gameNameEn":  "Crazy777",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s14.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.1,
                                                           "gameID":  "8c62471fd4e28c084a61811a3958f7a1"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "3a557646c3abb12201c0b8810a8c0966",
                                                           "gameNameEn":  "Money Coming Expand Bets",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s15.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  97.32,
                                                           "gameID":  "3a557646c3abb12201c0b8810a8c0966"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "0ec0aeb7aad8903bb6ee6b9b9460926a",
                                                           "gameNameEn":  "Super Ace Scratch",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s19.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.01,
                                                           "gameID":  "0ec0aeb7aad8903bb6ee6b9b9460926a"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "d6d14943efe13dd3bcf1428d0f702024",
                                                           "gameNameEn":  "Fortune Coins",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s17.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.25,
                                                           "gameID":  "d6d14943efe13dd3bcf1428d0f702024"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "a5acbbb7ae534d303f67cb447dc8723d",
                                                           "gameNameEn":  "Money Pot",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s18.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.18,
                                                           "gameID":  "a5acbbb7ae534d303f67cb447dc8723d"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "29c66f73e3916b8eb18c2bf78886927d",
                                                           "gameNameEn":  "Super Ace Joker",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s22.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.48,
                                                           "gameID":  "29c66f73e3916b8eb18c2bf78886927d"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "edef29b5eda8e2eaf721d7315491c51d",
                                                           "gameNameEn":  "Go Rush",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s20.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.01,
                                                           "gameID":  "edef29b5eda8e2eaf721d7315491c51d"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "7ed860eef313538545ff7aa2b9290cf9",
                                                           "gameNameEn":  "Jackpot Joker",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s21.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.25,
                                                           "gameID":  "7ed860eef313538545ff7aa2b9290cf9"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "4702eb871271aa62ef3f3d78f5d968c1",
                                                           "gameNameEn":  "Pirate Queen 2",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s26.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.18,
                                                           "gameID":  "4702eb871271aa62ef3f3d78f5d968c1"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "eba92b1d3abd5f0d37dfbe112abdf0e2",
                                                           "gameNameEn":  "Mega Ace",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s23.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.48,
                                                           "gameID":  "eba92b1d3abd5f0d37dfbe112abdf0e2"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "80aad2a10ae6a95068b50160d6c78897",
                                                           "gameNameEn":  "Super Ace Deluxe",
                                                           "imgUrl":  "https://365paisa1.com/JILI/s10.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.01,
                                                           "gameID":  "80aad2a10ae6a95068b50160d6c78897"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "007f5afeab86a47d96038324438c0c1f",
                                                           "gameNameEn":  "Fortune Coins 2",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s25.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.25,
                                                           "gameID":  "007f5afeab86a47d96038324438c0c1f"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "9a3b65e2ae5343df349356d548f3fc4b",
                                                           "gameNameEn":  "Wild Ace",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s29.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.18,
                                                           "gameID":  "9a3b65e2ae5343df349356d548f3fc4b"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "f2b04833d555ef9989748f9ecabd5249",
                                                           "gameNameEn":  "Fortune King Jackpot",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s27.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.48,
                                                           "gameID":  "f2b04833d555ef9989748f9ecabd5249"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "cc686634b4f953754b306317799f1f39",
                                                           "gameNameEn":  "Ali Baba",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s28.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.48,
                                                           "gameID":  "cc686634b4f953754b306317799f1f39"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "731e642b1fee94725e7313f3dfba8f45",
                                                           "gameNameEn":  "Lucky Jaguar",
                                                           "imgUrl":  "https://365paisa1.com/jili/jili/s30.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.25,
                                                           "gameID":  "731e642b1fee94725e7313f3dfba8f45"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "ae88afcb58415b7802e2c02c40816f17",
                                                           "gameNameEn":  "Sweet Magic",
                                                           "imgUrl":  "https://365paisa1.com/selimmm.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.18,
                                                           "gameID":  "ae88afcb58415b7802e2c02c40816f17"
                                                       },
                                                       {
                                                           "vendorId":  37,
                                                           "vendorCode":  "JILI",
                                                           "gameCode":  "397a27f0042be1a6f6a3e09c0ae6c057",
                                                           "gameNameEn":  "Jackpot Joker FEVER",
                                                           "imgUrl":  "https://365paisa1.com/selimmm1.png",
                                                           "imgUrl2":  "",
                                                           "winOdds":  96.48,
                                                           "gameID":  "397a27f0042be1a6f6a3e09c0ae6c057"
                                                       }
                                                   ],
                                 "clicksVideoTopList":  [
                                                            {
                                                                "vendorId":  "38",
                                                                "vendorCode":  "dreamgaming",
                                                                "gameCode":  "8737e1ef982bd7ba41ec02c1823626f9",
                                                                "gameNameEn":  "Airwave Roulette",
                                                                "imgUrl":  "https://ossimg.dogeclubimage.com/dogeclub/gamelogo/MG_Video/SMG_MGLiveGrand_AirwaveRoulette.png",
                                                                "imgUrl2":  "",
                                                                "winOdds":  0,
                                                                "gameID":  "8737e1ef982bd7ba41ec02c1823626f9"
                                                            },
                                                            {
                                                                "vendorId":  "38",
                                                                "vendorCode":  "ongaming",
                                                                "gameCode":  "3630a6a3c836afa6864578ef21f8fa93",
                                                                "gameNameEn":  "Amstel Roulette",
                                                                "imgUrl":  "https://ossimg.dogeclubimage.com/dogeclub/gamelogo/MG_Video/SMG_MGLiveGrand_AmstelRoulette.png",
                                                                "imgUrl2":  "",
                                                                "winOdds":  0,
                                                                "gameID":  "3630a6a3c836afa6864578ef21f8fa93"
                                                            },
                                                            {
                                                                "vendorId":  "38",
                                                                "vendorCode":  "dreamgaming",
                                                                "gameCode":  "8737e1ef982bd7ba41ec02c1823626f9",
                                                                "gameNameEn":  "Auto Roulette",
                                                                "imgUrl":  "https://ossimg.dogeclubimage.com/dogeclub/gamelogo/MG_Video/SMG_MGLiveGrand_AutoRoulette.png",
                                                                "imgUrl2":  "",
                                                                "winOdds":  0,
                                                                "gameID":  "8737e1ef982bd7ba41ec02c1823626f9"
                                                            },
                                                            {
                                                                "vendorId":  "38",
                                                                "vendorCode":  "ongaming",
                                                                "gameCode":  "3630a6a3c836afa6864578ef21f8fa93",
                                                                "gameNameEn":  "Blackjack Amsterdam",
                                                                "imgUrl":  "https://ossimg.dogeclubimage.com/dogeclub/gamelogo/MG_Video/SMG_MGLiveGrand_BlackjackAmsterdam.png",
                                                                "imgUrl2":  "",
                                                                "winOdds":  0,
                                                                "gameID":  "3630a6a3c836afa6864578ef21f8fa93"
                                                            },
                                                            {
                                                                "vendorId":  "38",
                                                                "vendorCode":  "dreamgaming",
                                                                "gameCode":  "8737e1ef982bd7ba41ec02c1823626f9",
                                                                "gameNameEn":  "Blackjack Berlin",
                                                                "imgUrl":  "https://ossimg.dogeclubimage.com/dogeclub/gamelogo/MG_Video/SMG_MGLiveGrand_BlackjackBerlin.png",
                                                                "imgUrl2":  "",
                                                                "winOdds":  0,
                                                                "gameID":  "8737e1ef982bd7ba41ec02c1823626f9"
                                                            },
                                                            {
                                                                "vendorId":  "38",
                                                                "vendorCode":  "ongaming",
                                                                "gameCode":  "3630a6a3c836afa6864578ef21f8fa93",
                                                                "gameNameEn":  "Blackjack Calgary",
                                                                "imgUrl":  "https://ossimg.dogeclubimage.com/dogeclub/gamelogo/MG_Video/SMG_MGLiveGrand_BlackjackCalgary.png",
                                                                "imgUrl2":  "",
                                                                "winOdds":  0,
                                                                "gameID":  "3630a6a3c836afa6864578ef21f8fa93"
                                                            },
                                                            {
                                                                "vendorId":  "38",
                                                                "vendorCode":  "dreamgaming",
                                                                "gameCode":  "8737e1ef982bd7ba41ec02c1823626f9",
                                                                "gameNameEn":  "Blackjack London",
                                                                "imgUrl":  "https://ossimg.dogeclubimage.com/dogeclub/gamelogo/MG_Video/SMG_MGLiveGrand_BlackjackLondon.png",
                                                                "imgUrl2":  "",
                                                                "winOdds":  0,
                                                                "gameID":  "8737e1ef982bd7ba41ec02c1823626f9"
                                                            },
                                                            {
                                                                "vendorId":  "38",
                                                                "vendorCode":  "ongaming",
                                                                "gameCode":  "3630a6a3c836afa6864578ef21f8fa93",
                                                                "gameNameEn":  "Blackjack Madrid",
                                                                "imgUrl":  "https://ossimg.dogeclubimage.com/dogeclub/gamelogo/MG_Video/SMG_MGLiveGrand_BlackjackMadrid.png",
                                                                "imgUrl2":  "",
                                                                "winOdds":  0,
                                                                "gameID":  "3630a6a3c836afa6864578ef21f8fa93"
                                                            },
                                                            {
                                                                "vendorId":  "38",
                                                                "vendorCode":  "dreamgaming",
                                                                "gameCode":  "8737e1ef982bd7ba41ec02c1823626f9",
                                                                "gameNameEn":  "Blackjack Manchester",
                                                                "imgUrl":  "https://ossimg.dogeclubimage.com/dogeclub/gamelogo/MG_Video/SMG_MGLiveGrand_BlackjackManchester.png",
                                                                "imgUrl2":  "",
                                                                "winOdds":  0,
                                                                "gameID":  "8737e1ef982bd7ba41ec02c1823626f9"
                                                            }
                                                        ]
                             },
                 "sport":  [
                               {
                                   "slotsTypeID":  25,
                                   "slotsName":  "Sportsbook",
                                   "vendorId":  25,
                                   "gameCode":  "07baf9e1388d32cd4cee0c0c91b23020",
                                   "vendorCode":  "Wickets9",
                                   "state":  1,
                                   "vendorImg":  "https://365paisa1.com/casino/sport9.png",
                                   "gameID":  "07baf9e1388d32cd4cee0c0c91b23020"
                               },
                               {
                                   "slotsTypeID":  8,
                                   "slotsName":  "568win",
                                   "gameCode":  "171ffc7c5df076a4a4aedf892cd43212",
                                   "vendorId":  8,
                                   "vendorCode":  "CMD",
                                   "state":  1,
                                   "vendorImg":  "https://365paisa1.com/casino/sport.png",
                                   "gameID":  "171ffc7c5df076a4a4aedf892cd43212"
                               }
                           ],
                 "video":  [
                               {
                                   "slotsTypeID":  7,
                                   "slotsName":  "EVOLIVEROW",
                                   "vendorId":  7,
                                   "vendorCode":  "EVOLIVEROW",
                                   "gameCode":  "e5df7dfd6dbb9336aa8ef8eeb07c7bc7",
                                   "state":  1,
                                   "vendorImg":  "https://365paisa1.com/casino/casino3.png",
                                   "gameID":  "e5df7dfd6dbb9336aa8ef8eeb07c7bc7"
                               },
                               {
                                   "slotsTypeID":  7,
                                   "slotsName":  "DG",
                                   "vendorId":  7,
                                   "vendorCode":  "PPLIVE",
                                   "gameCode":  "a1a01855da3f29d09a848e705ad76ea1",
                                   "state":  1,
                                   "vendorImg":  "https://365paisa1.com/casino/casino1.png",
                                   "gameID":  "a1a01855da3f29d09a848e705ad76ea1"
                               },
                               {
                                   "slotsTypeID":  38,
                                   "slotsName":  "sexy",
                                   "vendorId":  38,
                                   "vendorCode":  "ON",
                                   "gameCode":  "3630a6a3c836afa6864578ef21f8fa93",
                                   "state":  1,
                                   "vendorImg":  "https://365paisa1.com/casino/casino2.png",
                                   "gameID":  "3630a6a3c836afa6864578ef21f8fa93"
                               }
                           ],
                 "slot":  [
                              {
                                  "slotsTypeID":  18,
                                  "slotsName":  "JILI",
                                  "vendorId":  18,
                                  "vendorCode":  "JILI",
                                  "state":  1,
                                  "vendorImg":  "https://nsgame10.site/apiimages/BDGWin/vendorlogo/vendorlogo_20240321183353rwkf.png"
                              },
                              {
                                  "slotsTypeID":  87,
                                  "slotsName":  "INOUT",
                                  "vendorId":  87,
                                  "vendorCode":  "INOUT",
                                  "state":  1,
                                  "vendorImg":  "https://nsgame10.site/apiimages/BDGWin/apigameimages/vendorlogo_20240322155135ilqv.png"
                              },
                              {
                                  "slotsTypeID":  5,
                                  "slotsName":  "PG",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "state":  1,
                                  "vendorImg":  "https://nsgame10.site/apiimages/BDGWin/vendorlogo/vendorlogo_202404121129431pg2.png"
                              },
                              {
                                  "slotsTypeID":  4,
                                  "slotsName":  "MG",
                                  "vendorId":  4,
                                  "vendorCode":  "MG",
                                  "state":  1,
                                  "vendorImg":  "https://nsgame10.site/apiimages/BDGWin/vendorlogo/vendorlogo_20240321183534v51y.png"
                              },
                              {
                                  "slotsTypeID":  23,
                                  "slotsName":  "TADA",
                                  "vendorId":  23,
                                  "vendorCode":  "TADA",
                                  "state":  1,
                                  "vendorImg":  "https://nsgame10.site/apiimages/BDGWin/vendorlogo/vendorlogo_20240321183718x4pi.png"
                              },
                              {
                                  "slotsTypeID":  2,
                                  "slotsName":  "CQ9",
                                  "vendorId":  2,
                                  "vendorCode":  "CQ9",
                                  "state":  1,
                                  "vendorImg":  "https://nsgame10.site/apiimages/BDGWin/vendorlogo/vendorlogo_20240321183506uo8v.png"
                              }
                          ],
                 "chess":  [
                               {
                                   "slotsTypeID":  21,
                                   "slotsName":  "V8Card",
                                   "vendorId":  21,
                                   "vendorCode":  "V8Card",
                                   "state":  1,
                                   "vendorImg":  "https://nsgame10.site/apiimages/BDGWin/apigameimages/vendorlogo_20240306233853vhdr.png"
                               },
                               {
                                   "slotsTypeID":  19,
                                   "slotsName":  "Card365",
                                   "vendorId":  19,
                                   "vendorCode":  "Card365",
                                   "state":  1,
                                   "vendorImg":  "https://nsgame10.site/apiimages/BDGWin/apigameimages/vendorlogo_20240306233818ju4l.png"
                               }
                           ],
                 "fish":  [
                              {
                                  "gameID":  "99f07ed2226f37e22570325fc1c078f2",
                                  "gameNameEn":  "The Great Icescape",
                                  "img":  "https://365paisa1.com/PG/pg/s31.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "99f07ed2226f37e22570325fc1c078f2"
                              },
                              {
                                  "gameID":  "c98bb64436826fe9a2c62955ff70cba9",
                                  "gameNameEn":  "Wild Bounty Showdown",
                                  "img":  "https://365paisa1.com/PG/pg/s5.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "c98bb64436826fe9a2c62955ff70cba9"
                              },
                              {
                                  "gameID":  "2fa9a84d096d6ff0bab53f81b79876c8",
                                  "gameNameEn":  "Treasures of Aztec",
                                  "img":  "https://365paisa1.com/PG/pg/s7.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "2fa9a84d096d6ff0bab53f81b79876c8"
                              },
                              {
                                  "gameID":  "c268154a85669eea35aa46387834ac76",
                                  "gameNameEn":  "Anubis Wrath",
                                  "img":  "https://365paisa1.com/PG/pg/s6.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "c268154a85669eea35aa46387834ac76"
                              },
                              {
                                  "gameID":  "f08cc025e23ee049b570517867c74be0",
                                  "gameNameEn":  "Pinata Wins",
                                  "img":  "https://365paisa1.com/PG/pg/s10.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "f08cc025e23ee049b570517867c74be0"
                              },
                              {
                                  "gameID":  "95fc290bb05c07b5aad1a054eba4dcc4",
                                  "gameNameEn":  "Wild Bandito",
                                  "img":  "https://365paisa1.com/PG/pg/s8.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "95fc290bb05c07b5aad1a054eba4dcc4"
                              },
                              {
                                  "gameID":  "e1b4c6b95746d519228744771f15fe4b",
                                  "gameNameEn":  "Lucky Neko",
                                  "img":  "https://365paisa1.com/PG/pg/s9.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "e1b4c6b95746d519228744771f15fe4b"
                              },
                              {
                                  "gameID":  "1189baca156e1bbbecc3b26651a63565",
                                  "gameNameEn":  "Mahjong Ways",
                                  "img":  "https://365paisa1.com/PG/pg/s13.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "1189baca156e1bbbecc3b26651a63565"
                              },
                              {
                                  "gameID":  "e175cdd3215a02f5539cc8354a149b75",
                                  "gameNameEn":  "Fortune Rabbit",
                                  "img":  "https://365paisa1.com/PG/pg/s11.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "e175cdd3215a02f5539cc8354a149b75"
                              },
                              {
                                  "gameID":  "d3aad7ca1486eb3f145e2c43f38b559c",
                                  "gameNameEn":  "Fortune Gods",
                                  "img":  "https://365paisa1.com/PG/pg/s32.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "d3aad7ca1486eb3f145e2c43f38b559c"
                              },
                              {
                                  "gameID":  "e61bde75d590e943d2c5c6d432b29b46",
                                  "gameNameEn":  "Mystic Potion",
                                  "img":  "https://365paisa1.com/PG/pg/s16.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "e61bde75d590e943d2c5c6d432b29b46"
                              },
                              {
                                  "gameID":  "08d92dc2ca14f42c681b44297386d600",
                                  "gameNameEn":  "Asgardian Rising",
                                  "img":  "https://365paisa1.com/PG/pg/s14.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "08d92dc2ca14f42c681b44297386d600"
                              },
                              {
                                  "gameID":  "c5435a8a73707a3a8bb4fe8baaaef3d2",
                                  "gameNameEn":  "Fortune Dragon",
                                  "img":  "https://365paisa1.com/PG/pg/s3.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "c5435a8a73707a3a8bb4fe8baaaef3d2"
                              },
                              {
                                  "gameID":  "20aae27b3d4b06dc88881f97405b3a10",
                                  "gameNameEn":  "Poker Kingdom Win",
                                  "img":  "https://365paisa1.com/PG/pg/s1.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "20aae27b3d4b06dc88881f97405b3a10"
                              },
                              {
                                  "gameID":  "9b93cb0dc46d847864c87ed42a3428bb",
                                  "gameNameEn":  "Wild Ape #3258",
                                  "img":  "https://365paisa1.com/PG/pg/s2.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "9b93cb0dc46d847864c87ed42a3428bb"
                              },
                              {
                                  "gameID":  "5bea5667515cb7a31f6f07dc3caa13b1",
                                  "gameNameEn":  "Cocktail Nights",
                                  "img":  "https://365paisa1.com/PG/pg/s22.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "5bea5667515cb7a31f6f07dc3caa13b1"
                              },
                              {
                                  "gameID":  "3810e528e0abb8ce1cd7ddc2ece005c0",
                                  "gameNameEn":  "Double Fortune",
                                  "img":  "https://365paisa1.com/PG/pg/s20.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "3810e528e0abb8ce1cd7ddc2ece005c0"
                              },
                              {
                                  "gameID":  "5af319aeb42d316100789fa1670ed869",
                                  "gameNameEn":  "Oishi Delights",
                                  "img":  "https://365paisa1.com/PG/pg/s21.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "5af319aeb42d316100789fa1670ed869"
                              },
                              {
                                  "gameID":  "6e37f6e1b6042c2147866c4d86206979",
                                  "gameNameEn":  "Legend of Perseus",
                                  "img":  "https://365paisa1.com/PG/pg/s25.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "6e37f6e1b6042c2147866c4d86206979"
                              },
                              {
                                  "gameID":  "fedfca553a97a791a3a41c4f1e3bff58",
                                  "gameNameEn":  "Ways of the Qilin",
                                  "img":  "https://365paisa1.com/PG/pg/s24.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "fedfca553a97a791a3a41c4f1e3bff58"
                              },
                              {
                                  "gameID":  "a2fd6b0cadc8fefccfb0d063b1f81d85",
                                  "gameNameEn":  "Midas Fortune",
                                  "img":  "https://365paisa1.com/PG/pg/s28.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "a2fd6b0cadc8fefccfb0d063b1f81d85"
                              },
                              {
                                  "gameID":  "8004c0cdbe396264d035b7a4aba58021",
                                  "gameNameEn":  "Mr. Treasure’s Fortune",
                                  "img":  "https://365paisa1.com/PG/pg/s26.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "8004c0cdbe396264d035b7a4aba58021"
                              },
                              {
                                  "gameID":  "0a8772ad753cfce2c03c0599ad60c74c",
                                  "gameNameEn":  "Jack Frost Winter",
                                  "img":  "https://365paisa1.com/PG/pg/s27.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "0a8772ad753cfce2c03c0599ad60c74c"
                              },
                              {
                                  "gameID":  "ba2adf72179e1ead9e3dae8f0a7d4c07",
                                  "gameNameEn":  "Mahjong Ways 2",
                                  "img":  "https://365paisa1.com/PG/pg/s30.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "ba2adf72179e1ead9e3dae8f0a7d4c07"
                              },
                              {
                                  "gameID":  "877c9b2ec1c5e0505129315948f9bbfa",
                                  "gameNameEn":  "Gemstones Gold",
                                  "img":  "https://365paisa1.com/PG/pg/s29.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "877c9b2ec1c5e0505129315948f9bbfa"
                              },
                              {
                                  "gameID":  "5c371d9fca6109c954de93ac7986c5db",
                                  "gameNameEn":  "Rooster Rumble",
                                  "img":  "https://365paisa1.com/PG/pg/s19.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "5c371d9fca6109c954de93ac7986c5db"
                              },
                              {
                                  "gameID":  "8dcea650a5a4d96530a77e6df8f61923",
                                  "gameNameEn":  "Ganesha Gold",
                                  "img":  "https://365paisa1.com/PG/pg/s17.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "8dcea650a5a4d96530a77e6df8f61923"
                              },
                              {
                                  "gameID":  "e4772d4ef1de4217915c678d0d1722a8",
                                  "gameNameEn":  "Yakuza Honor",
                                  "img":  "https://365paisa1.com/PG/pg/s18.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "e4772d4ef1de4217915c678d0d1722a8"
                              },
                              {
                                  "gameID":  "27237d7e8d9b183c92fa9f6ab9832edc",
                                  "gameNameEn":  "Candy Burst",
                                  "img":  "https://365paisa1.com/PG/pg/s36.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "27237d7e8d9b183c92fa9f6ab9832edc"
                              },
                              {
                                  "gameID":  "b769cb768fa25699ddb695933bde781a",
                                  "gameNameEn":  "Incan Wonders",
                                  "img":  "https://365paisa1.com/PG/pg/s33.png",
                                  "vendorId":  5,
                                  "vendorCode":  "PG",
                                  "imgUrl2":  "",
                                  "customGameType":  0,
                                  "gameCode":  "b769cb768fa25699ddb695933bde781a"
                              }
                          ],
                 "flash":  [
                               {
                                   "gameID":  "a04d1f3eb8ccec8a4823bdf18e3f0e84",
                                   "gameNameEn":  "Aviator",
                                   "img":  "https://ossimg.crhhh.com/bdtgame/gamelogo/SPRIBE/22001.png",
                                   "vendorId":  23,
                                   "vendorCode":  "TB_Chess",
                                   "imgUrl2":  "",
                                   "customGameType":  0,
                                   "gameCode":  "a04d1f3eb8ccec8a4823bdf18e3f0e84"
                               },
                               {
                                   "gameID":  "8a87aae7a3624d284306e9c6fe1b3e9c",
                                   "gameNameEn":  "Dice",
                                   "img":  "https://365paisa1.com/spribe/s1.png",
                                   "vendorId":  23,
                                   "vendorCode":  "TB_Chess",
                                   "imgUrl2":  "",
                                   "customGameType":  0,
                                   "gameCode":  "8a87aae7a3624d284306e9c6fe1b3e9c"
                               },
                               {
                                   "gameID":  "c68a515f0b3b10eec96cf6d33299f4e2",
                                   "gameNameEn":  "Goal",
                                   "img":  "https://365paisa1.com/spribe/s5.png",
                                   "vendorId":  23,
                                   "vendorCode":  "TB_Chess",
                                   "imgUrl2":  "",
                                   "customGameType":  0,
                                   "gameCode":  "c68a515f0b3b10eec96cf6d33299f4e2"
                               },
                               {
                                   "gameID":  "a669c993b0e1f1b7da100fcf95516bdf",
                                   "gameNameEn":  "Hi Lo",
                                   "img":  "https://365paisa1.com/spribe/s7.png",
                                   "vendorId":  23,
                                   "vendorCode":  "TB_Chess",
                                   "imgUrl2":  "",
                                   "customGameType":  0,
                                   "gameCode":  "a669c993b0e1f1b7da100fcf95516bdf"
                               },
                               {
                                   "gameID":  "b31720b3cd65d917a1a96ef61a72b672",
                                   "gameNameEn":  "Hotline",
                                   "img":  "https://365paisa1.com/spribe/s2.png",
                                   "vendorId":  23,
                                   "vendorCode":  "TB_Chess",
                                   "imgUrl2":  "",
                                   "customGameType":  0,
                                   "gameCode":  "b31720b3cd65d917a1a96ef61a72b672"
                               },
                               {
                                   "gameID":  "c311eb4bbba03b105d150504931f2479",
                                   "gameNameEn":  "Keno",
                                   "img":  "https://365paisa1.com/spribe/s11.png",
                                   "vendorId":  23,
                                   "vendorCode":  "TB_Chess",
                                   "imgUrl2":  "",
                                   "customGameType":  0,
                                   "gameCode":  "c311eb4bbba03b105d150504931f2479"
                               },
                               {
                                   "gameID":  "5c4a12fb0a9b296d9b0d5f9e1cd41d65",
                                   "gameNameEn":  "Mines",
                                   "img":  "https://365paisa1.com/spribe/s4.png",
                                   "vendorId":  23,
                                   "vendorCode":  "TB_Chess",
                                   "imgUrl2":  "",
                                   "customGameType":  0,
                                   "gameCode":  "5c4a12fb0a9b296d9b0d5f9e1cd41d65"
                               },
                               {
                                   "gameID":  "ef323466f031cc0d206fd20ca8149f42",
                                   "gameNameEn":  "Pilot Chicken",
                                   "img":  "https://365paisa1.com/spribe/s10.png",
                                   "vendorId":  23,
                                   "vendorCode":  "TB_Chess",
                                   "imgUrl2":  "",
                                   "customGameType":  0,
                                   "gameCode":  "ef323466f031cc0d206fd20ca8149f42"
                               },
                               {
                                   "gameID":  "6ab7a4fe5161936012d6b06143918223",
                                   "gameNameEn":  "Plinko",
                                   "img":  "https://365paisa1.com/spribe/s9.png",
                                   "vendorId":  23,
                                   "vendorCode":  "TB_Chess",
                                   "imgUrl2":  "",
                                   "customGameType":  0,
                                   "gameCode":  "6ab7a4fe5161936012d6b06143918223"
                               },
                               {
                                   "gameID":  "ad5973a7625b5d18257e64340fe22ca1",
                                   "gameNameEn":  "Trader",
                                   "img":  "https://365paisa1.com/spribe/s6.png",
                                   "vendorId":  23,
                                   "vendorCode":  "TB_Chess",
                                   "imgUrl2":  "",
                                   "customGameType":  0,
                                   "gameCode":  "ad5973a7625b5d18257e64340fe22ca1"
                               },
                               {
                                   "gameID":  "de88f202c5a8beeaccabbd944f8acfbf",
                                   "gameNameEn":  "Balloon",
                                   "img":  "https://ossimg.dogeclubimage.com/dogeclub/gamelogo/SPRIBE/balloon.png",
                                   "vendorId":  20,
                                   "vendorCode":  "TB_Chess",
                                   "imgUrl2":  "",
                                   "customGameType":  0,
                                   "gameCode":  "de88f202c5a8beeaccabbd944f8acfbf"
                               },
                               {
                                   "gameID":  "9dc7ac6155c5a19c1cc204853e426367",
                                   "gameNameEn":  "Mini Roulette",
                                   "img":  "https://ossimg.dogeclubimage.com/dogeclub/gamelogo/SPRIBE/mini-roulette.png",
                                   "vendorId":  20,
                                   "vendorCode":  "TB_Chess",
                                   "imgUrl2":  "",
                                   "customGameType":  0,
                                   "gameCode":  "9dc7ac6155c5a19c1cc204853e426367"
                               }
                           ],
                 "lottery":  [
                                 {
                                     "id":  1,
                                     "categoryCode":  "Win Go",
                                     "categoryName":  "WinGo彩票",
                                     "state":  1,
                                     "sort":  0,
                                     "categoryImg":  "https://ossimg.jaiclubpay.com/jaiclub/lotterycategory/lotterycategory_20260326112956t35m.png",
                                     "wingoAmount":  null,
                                     "k3Amount":  null,
                                     "fiveDAmount":  null,
                                     "trxWingoAmount":  null,
                                     "gameCode":  "WinGo_30S"
                                 },
                                 {
                                     "id":  9,
                                     "categoryCode":  "Motor Racing",
                                     "categoryName":  "Motor Racing",
                                     "state":  1,
                                     "sort":  9,
                                     "categoryImg":  "https://ossimg.bdg123456.com/BDGWin/lotterycategory/lotterycategory_20250516034354gi1v.png",
                                     "wingoAmount":  null,
                                     "k3Amount":  null,
                                     "fiveDAmount":  null,
                                     "trxWingoAmount":  null,
                                     "gameCode":  "MotorRacing_1M",
                                     "gameID":  "MotorRacing_1M"
                                 },
                                 {
                                     "id":  2,
                                     "categoryCode":  "K3",
                                     "categoryName":  "K3彩票",
                                     "state":  1,
                                     "sort":  0,
                                     "categoryImg":  "https://ossimg.jaiclubpay.com/jaiclub/lotterycategory/lotterycategory_202603261130075lqh.png",
                                     "wingoAmount":  null,
                                     "k3Amount":  null,
                                     "fiveDAmount":  null,
                                     "trxWingoAmount":  null,
                                     "gameCode":  "K3_1M"
                                 },
                                 {
                                     "id":  3,
                                     "categoryCode":  "5D",
                                     "categoryName":  "5D彩票",
                                     "state":  1,
                                     "sort":  0,
                                     "categoryImg":  "https://ossimg.jaiclubpay.com/jaiclub/lotterycategory/lotterycategory_20260326113016kjsa.png",
                                     "wingoAmount":  null,
                                     "k3Amount":  null,
                                     "fiveDAmount":  null,
                                     "trxWingoAmount":  null,
                                     "gameCode":  "D5_1M"
                                 },
                                 {
                                     "id":  4,
                                     "categoryCode":  "Trx Win Go",
                                     "categoryName":  "TrxWinGo彩票",
                                     "state":  1,
                                     "sort":  7,
                                     "categoryImg":  "https://ossimg.jaiclubpay.com/jaiclub/lotterycategory/lotterycategory_20260326113034g5yq.png",
                                     "wingoAmount":  null,
                                     "k3Amount":  null,
                                     "fiveDAmount":  null,
                                     "trxWingoAmount":  null,
                                     "gameCode":  "TrxWinGo_1M"
                                 }
                             ],
                 "awardRecordList":  [
                                         {
                                             "orderId":  12567680,
                                             "userId":  698590,
                                             "userPhoto":  "9",
                                             "userName":  "919828801913",
                                             "gameName":  "Fortune Gems 2",
                                             "imgUrl":  "https://ossimg.Big-Mumadmin888.com/bigmumbai/gamelogo/JILI/223.png",
                                             "imgUrl2":  "",
                                             "multiple":  66.67,
                                             "bonusAmount":  300,
                                             "multipleName":  "50-999999",
                                             "createTime":  "2025-03-03 14:18:00"
                                         },
                                         {
                                             "orderId":  12567679,
                                             "userId":  4270140,
                                             "userPhoto":  "6",
                                             "userName":  "917870087696",
                                             "gameName":  "Super Ace",
                                             "imgUrl":  "https://ossimg.Big-Mumadmin888.com/bigmumbai/gamelogo/JILI/49.png",
                                             "imgUrl2":  "",
                                             "multiple":  35.3,
                                             "bonusAmount":  10,
                                             "multipleName":  "35-49",
                                             "createTime":  "2025-03-03 14:18:00"
                                         },
                                         {
                                             "orderId":  12567678,
                                             "userId":  1771451,
                                             "userPhoto":  "1",
                                             "userName":  "919575354782",
                                             "gameName":  "Money Coming",
                                             "imgUrl":  "https://ossimg.Big-Mumadmin888.com/bigmumbai/gamelogo/JILI/51.png",
                                             "imgUrl2":  "",
                                             "multiple":  100,
                                             "bonusAmount":  50,
                                             "multipleName":  "50-999999",
                                             "createTime":  "2025-03-03 14:18:00"
                                         },
                                         {
                                             "orderId":  12567677,
                                             "userId":  2105155,
                                             "userPhoto":  "1",
                                             "userName":  "919198997855",
                                             "gameName":  "Aviator",
                                             "imgUrl":  "https://ossimg.Big-Mumadmin888.com/bigmumbai/gamelogo/SPRIBE/22001.png",
                                             "imgUrl2":  "",
                                             "multiple":  21.38,
                                             "bonusAmount":  10,
                                             "multipleName":  "20-34",
                                             "createTime":  "2025-03-03 14:18:00"
                                         },
                                         {
                                             "orderId":  12567676,
                                             "userId":  1248688,
                                             "userPhoto":  "1",
                                             "userName":  "918018256757",
                                             "gameName":  "Money Coming",
                                             "imgUrl":  "https://ossimg.Big-Mumadmin888.com/bigmumbai/gamelogo/JILI/51.png",
                                             "imgUrl2":  "",
                                             "multiple":  30,
                                             "bonusAmount":  5,
                                             "multipleName":  "20-34",
                                             "createTime":  "2025-03-03 14:18:00"
                                         },
                                         {
                                             "orderId":  12567675,
                                             "userId":  5170159,
                                             "userPhoto":  "1",
                                             "userName":  "917894640990",
                                             "gameName":  "Money Coming",
                                             "imgUrl":  "https://ossimg.Big-Mumadmin888.com/bigmumbai/gamelogo/JILI/51.png",
                                             "imgUrl2":  "",
                                             "multiple":  23,
                                             "bonusAmount":  5,
                                             "multipleName":  "20-34",
                                             "createTime":  "2025-03-03 14:18:00"
                                         },
                                         {
                                             "orderId":  12567674,
                                             "userId":  5267211,
                                             "userPhoto":  "1",
                                             "userName":  "916207187004",
                                             "gameName":  "Money Coming",
                                             "imgUrl":  "https://ossimg.Big-Mumadmin888.com/bigmumbai/gamelogo/JILI/51.png",
                                             "imgUrl2":  "",
                                             "multiple":  20.2,
                                             "bonusAmount":  10,
                                             "multipleName":  "20-34",
                                             "createTime":  "2025-03-03 14:18:00"
                                         },
                                         {
                                             "orderId":  12567673,
                                             "userId":  1445592,
                                             "userPhoto":  "1",
                                             "userName":  "919982079135",
                                             "gameName":  "Money Coming",
                                             "imgUrl":  "https://ossimg.Big-Mumadmin888.com/bigmumbai/gamelogo/JILI/51.png",
                                             "imgUrl2":  "",
                                             "multiple":  22,
                                             "bonusAmount":  5,
                                             "multipleName":  "20-34",
                                             "createTime":  "2025-03-03 14:18:00"
                                         },
                                         {
                                             "orderId":  12567672,
                                             "userId":  4397697,
                                             "userPhoto":  "1",
                                             "userName":  "917878816365",
                                             "gameName":  "Fortune Gems 2",
                                             "imgUrl":  "https://ossimg.Big-Mumadmin888.com/bigmumbai/gamelogo/JILI/223.png",
                                             "imgUrl2":  "",
                                             "multiple":  36,
                                             "bonusAmount":  10,
                                             "multipleName":  "35-49",
                                             "createTime":  "2025-03-03 14:18:00"
                                         },
                                         {
                                             "orderId":  12567671,
                                             "userId":  5739066,
                                             "userPhoto":  "1",
                                             "userName":  "919862351307",
                                             "gameName":  "Money Coming",
                                             "imgUrl":  "https://ossimg.Big-Mumadmin888.com/bigmumbai/gamelogo/JILI/51.png",
                                             "imgUrl2":  "",
                                             "multiple":  22.2,
                                             "bonusAmount":  5,
                                             "multipleName":  "20-34",
                                             "createTime":  "2025-03-03 14:18:00"
                                         }
                                     ],
                 "inout":  [
                               {
                                   "gameID":  "2126c5c458316ba1f2df65b387b60408",
                                   "gameNameEn":  "Chicken Road",
                                   "img":  "https://luckmedia.link/iog_chicken_road/thumb_3_4_custom.webp",
                                   "vendorId":  87,
                                   "vendorCode":  "INOUT",
                                   "imgUrl2":  "",
                                   "customGameType":  0,
                                   "gameCode":  "2126c5c458316ba1f2df65b387b60408"
                               },
                               {
                                   "gameID":  "562b299961b0ec40f252a832453c67b0",
                                   "gameNameEn":  "Cricket",
                                   "img":  "https://luckmedia.link/iog_chicken_road_2/thumb_3_4_custom.webp",
                                   "vendorId":  87,
                                   "vendorCode":  "INOUT",
                                   "imgUrl2":  "",
                                   "customGameType":  0,
                                   "gameCode":  "562b299961b0ec40f252a832453c67b0"
                               },
                               {
                                   "gameID":  "bdfb23c974a2517198c5443adeea77a8",
                                   "gameNameEn":  "Super Ace",
                                   "img":  "https://luckmedia.link/iog_aviafly/thumb_3_4_custom.webp",
                                   "vendorId":  37,
                                   "vendorCode":  "jili",
                                   "imgUrl2":  "",
                                   "customGameType":  0,
                                   "gameCode":  "bdfb23c974a2517198c5443adeea77a8"
                               },
                               {
                                   "gameID":  "81c4999d70b1ebbb7cf89d8e41ad493c",
                                   "gameNameEn":  "Limbo",
                                   "img":  "https://luckmedia.link/iog_limbo/thumb_3_4_custom.webp",
                                   "vendorId":  87,
                                   "vendorCode":  "INOUT",
                                   "imgUrl2":  "",
                                   "customGameType":  0,
                                   "gameCode":  "81c4999d70b1ebbb7cf89d8e41ad493c"
                               },
                               {
                                   "gameID":  "3a3affa176283107288f5da3698ffe7c",
                                   "gameNameEn":  "Lucky Mines",
                                   "img":  "https://luckmedia.link/iog_luckymines/thumb_3_4_custom.webp",
                                   "vendorId":  87,
                                   "vendorCode":  "INOUT",
                                   "imgUrl2":  "",
                                   "customGameType":  0,
                                   "gameCode":  "3a3affa176283107288f5da3698ffe7c"
                               },
                               {
                                   "gameID":  "9b48efcf6b18b12fdd7dc8efe9ae971e",
                                   "gameNameEn":  "Coinflip",
                                   "img":  "https://luckmedia.link/iog_coinflip/thumb_3_4_custom.webp",
                                   "vendorId":  87,
                                   "vendorCode":  "INOUT",
                                   "imgUrl2":  "",
                                   "customGameType":  0,
                                   "gameCode":  "9b48efcf6b18b12fdd7dc8efe9ae971e"
                               }
                           ],
                 "pragmaticplay":  [
                                       {
                                           "gameID":  "6204fe7f30c5c56146f201218ee3ea5e",
                                           "gameNameEn":  "Bigger Barn House Bonanza",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera2/s32.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "6204fe7f30c5c56146f201218ee3ea5e"
                                       },
                                       {
                                           "gameID":  "cb5d7e55024f427d18297db3d3959cb4",
                                           "gameNameEn":  "Cyber Heist",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera2/s33.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "cb5d7e55024f427d18297db3d3959cb4"
                                       },
                                       {
                                           "gameID":  "3fa8affb9baf4dffa777a4971433d34c",
                                           "gameNameEn":  "Fortune Ace Super Scatter",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera/s3.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "3fa8affb9baf4dffa777a4971433d34c"
                                       },
                                       {
                                           "gameID":  "95c391563f83181c52c5de366a89b9b9",
                                           "gameNameEn":  "High Flyer (Crash Game)",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera/s7.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "95c391563f83181c52c5de366a89b9b9"
                                       },
                                       {
                                           "gameID":  "4ae52ed2e1a8c353878ba65ed7791ac4",
                                           "gameNameEn":  "Gates of Olympus 1000",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera/s5.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "4ae52ed2e1a8c353878ba65ed7791ac4"
                                       },
                                       {
                                           "gameID":  "05d693169ee6aeec45d6328c7fc57a43",
                                           "gameNameEn":  "Aztec Gems",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera/s6.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "05d693169ee6aeec45d6328c7fc57a43"
                                       },
                                       {
                                           "gameID":  "880a68222d05a3697055d523d574cb2b",
                                           "gameNameEn":  "Gates of Olympus Super Scatter",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera/s10.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "880a68222d05a3697055d523d574cb2b"
                                       },
                                       {
                                           "gameID":  "b04d9cd89be5d93602d9a484f8bfe575",
                                           "gameNameEn":  "Fire Strike",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera/s8.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "b04d9cd89be5d93602d9a484f8bfe575"
                                       },
                                       {
                                           "gameID":  "3b2630042452d3e6b79054e4e96a2bf3",
                                           "gameNameEn":  "Sugar Rush 1000",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera/s9.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "3b2630042452d3e6b79054e4e96a2bf3"
                                       },
                                       {
                                           "gameID":  "4c2f18fa61c0becd4706a6da1961b020",
                                           "gameNameEn":  "Limbo+",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera/s13.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "4c2f18fa61c0becd4706a6da1961b020"
                                       },
                                       {
                                           "gameID":  "a15d8418eaff591cfa5856a441f6b3eb",
                                           "gameNameEn":  "Fortune Ace",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera/s11.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "a15d8418eaff591cfa5856a441f6b3eb"
                                       },
                                       {
                                           "gameID":  "91cd6233551cd56e70d900794ed728e3",
                                           "gameNameEn":  "Wild West Gold Blazing Bounty",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera/s12.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "91cd6233551cd56e70d900794ed728e3"
                                       },
                                       {
                                           "gameID":  "4ae52ed2e1a8c353878ba65ed7791ac4",
                                           "gameNameEn":  "Gates of Olympus Pachi",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera/s14.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "4ae52ed2e1a8c353878ba65ed7791ac4"
                                       },
                                       {
                                           "gameID":  "3e852870675170f0fd62ae330b807275",
                                           "gameNameEn":  "Sugar Rush Super Scatter",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera/s15.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "3e852870675170f0fd62ae330b807275"
                                       },
                                       {
                                           "gameID":  "6aeb8420d03722cfd4b66d91e17758ba",
                                           "gameNameEn":  "Sugar Rush",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera/s17.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "6aeb8420d03722cfd4b66d91e17758ba"
                                       },
                                       {
                                           "gameID":  "c4f954079d20b7eb6da49c9fce1f9b08",
                                           "gameNameEn":  "Mines+",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera/s18.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "c4f954079d20b7eb6da49c9fce1f9b08"
                                       },
                                       {
                                           "gameID":  "5808b410c759813d771f31af6c3f4381",
                                           "gameNameEn":  "888 Gold",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera/s23.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "5808b410c759813d771f31af6c3f4381"
                                       },
                                       {
                                           "gameID":  "25af131fa95bfd1b9bf14aade403493a",
                                           "gameNameEn":  "Treasures of Osiris",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera/s20.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "25af131fa95bfd1b9bf14aade403493a"
                                       },
                                       {
                                           "gameID":  "259ad8e105ef217dad404bffe0fff257",
                                           "gameNameEn":  "Mahjong Wins Super Scatter",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera/s22.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "259ad8e105ef217dad404bffe0fff257"
                                       },
                                       {
                                           "gameID":  "8007344e017bdd5c2d5d0d4eb43c3b2b",
                                           "gameNameEn":  "Lucky Tiger",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera/s24.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "8007344e017bdd5c2d5d0d4eb43c3b2b"
                                       },
                                       {
                                           "gameID":  "9438170762baae7b28aa15975d28acee",
                                           "gameNameEn":  "Happy Nets",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera2/s25.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "9438170762baae7b28aa15975d28acee"
                                       },
                                       {
                                           "gameID":  "0b6df3ffc8f38d0e39fccee31e1edf2b",
                                           "gameNameEn":  "Hand of Midas 2",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera2/s26.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "0b6df3ffc8f38d0e39fccee31e1edf2b"
                                       },
                                       {
                                           "gameID":  "0b6df3ffc8f38d0e39fccee31e1edf2b",
                                           "gameNameEn":  "Big Bass Boxing Bonus Round",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera2/s27.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "0b6df3ffc8f38d0e39fccee31e1edf2b"
                                       },
                                       {
                                           "gameID":  "e302e6030d750c678a0ddd351d6d8ec9",
                                           "gameNameEn":  "Big Bass Mission Fishin’",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera2/s28.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "e302e6030d750c678a0ddd351d6d8ec9"
                                       },
                                       {
                                           "gameID":  "fbe795aa609d23d09688d82498d38f50",
                                           "gameNameEn":  "Lucky’s Wild Pub",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera2/s29.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "fbe795aa609d23d09688d82498d38f50"
                                       },
                                       {
                                           "gameID":  "d64563c0afd930f39be01d6c8d9fb91b",
                                           "gameNameEn":  "Lucky Mouse",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera2/s34.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "d64563c0afd930f39be01d6c8d9fb91b"
                                       },
                                       {
                                           "gameID":  "bda320d9d63d12432d578b1f97855087",
                                           "gameNameEn":  "Wisdom of Athena",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera2/s35.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "bda320d9d63d12432d578b1f97855087"
                                       },
                                       {
                                           "gameID":  "aaea3a95f3e5ce36e4beea92c71ed912",
                                           "gameNameEn":  "Big Bass Secrets of the Golden Lake",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera2/s36.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "aaea3a95f3e5ce36e4beea92c71ed912"
                                       },
                                       {
                                           "gameID":  "12afb282e7568a94bd22aeb7e87a29ec",
                                           "gameNameEn":  "Mustang Gold",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera2/s37.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "12afb282e7568a94bd22aeb7e87a29ec"
                                       },
                                       {
                                           "gameID":  "09427b3980780ea41a9f15dda6b2726c",
                                           "gameNameEn":  "Saiyan Mania",
                                           "img":  "https://365paisa1.com/PRAGMATICPLAY/pera2/s38.png",
                                           "vendorId":  99,
                                           "vendorCode":  "PRAGMATIC",
                                           "imgUrl2":  "",
                                           "customGameType":  0,
                                           "gameCode":  "09427b3980780ea41a9f15dda6b2726c"
                                       }
                                   ]
             },
    "code":  0,
    "msg":  "Succeed",
    "msgCode":  0,
    "serviceNowTime":  "\u0027 . $serviceNowTimeFormatted . \u0027"
}';

$data = json_decode($jsonData, true);
$data['serviceNowTime'] = $serviceNowTimeFormatted;

$response = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

header('Content-Type: application/json; charset=utf-8');
echo $response;
?>
