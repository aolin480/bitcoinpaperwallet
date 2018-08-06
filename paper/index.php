<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ignition Coin Paper</title>

    <script src="assets/jquery/jquery-2.2.4.min.js" integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="assets/bootstrap4/bootstrap.min.css" integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">

    <script src="assets/bootstrap4/tether.min.js"></script>
    <script src="assets/bootstrap4/bootstrap.min.js" integrity="sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="assets/css/styles.css">

    <?php include '../includes/scripts/scripts-header.php'; ?>
    <?php // include '../includes/styles.php'; ?>
</head>
<body>
    <div id="busyblock"></div>
    <div id="main">
        <div class="d-none">
            <div id="culturemenu">
                <span><a href="?culture=en" id="cultureen" class="selected">English</a></span> |
                <span><a href="?culture=es" id="culturees">Español</a></span> |
                <span><a href="?culture=fr" id="culturefr">Français</a></span> |
                <span><a href="?culture=el" id="cultureel">ελληνικά</a></span> |
                <span><a href="?culture=it" id="cultureit">italiano</a></span> |
                <span><a href="?culture=de" id="culturede">Deutsch</a></span> |
                <span><a href="?culture=cs" id="culturecs">Česky</a></span> |
                <span><a href="?culture=hu" id="culturehu">Magyar</a></span> |
                <span><a href="?culture=jp" id="culturejp">日本語</a></span> |
                <span><a href="?culture=zh-cn" id="culturezh-cn">简体中文</a></span> |
                <span><a href="?culture=ru" id="cultureru">Русский</a></span>
            </div>
        </div>

        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="masthead">
                        <img src="images/logo.png" alt="Ignition Coin Paper">
                    </div>
                </div>
            </div>
        </div>

        <div class="wallet-area">
            <div id="generate">
                <div class="container">
                    <div class="row">
                        <div class="col-6">
                            <input type="text" id="generatekeyinput" data-lpignore="true" autocomplete="off" onkeydown="ninja.seeder.seedKeyPress(event);" placeholder="Type random characters"/><br />
                            <div id="guilloche" onclick="SecureRandom.seedTime();" onmousemove="ninja.seeder.seed(event);"></div>

                            <div class="mousemovelimit-wrapper">seeds left:<span id="mousemovelimit">0</span></div>

                            <?php /*
                            <div id="generate">
                                <span id="generatelabelbitcoinaddress">Generating Ignition Coin Address...</span><br />
                                <span id="generatelabelmovemouse">MOVE your mouse around to add some extra randomness... </span>
                                <span id="mousemovelimit"></span><br />
                                <span id="generatelabelkeypress">OR type some random characters into this textbox</span>
                                <input type="text" id="generatekeyinput" onkeydown="ninja.seeder.seedKeyPress(event);" /><br />
                                <div id="seedpooldisplay"></div>
                            </div>
                            */ ?>
                            <div class="d-none">
                                <div id="seedpoolarea"><textarea rows="16" cols="62" id="seedpool"></textarea></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <h2 class="primary">HOW TO USE</h2>
                            <p>
                                Move mouse around the area on the left or type in random keys in input box to begin generating random sequence seeds that will be used to generate your Ignition Coin Paper Wallet <strong>OR</strong> click on the <a href="#" onclick="ninja.seeder.seedCount = ninja.seeder.seedLimit; ninja.seeder.seed();">Generate Wallet</a> button below to automatically generate an Ignition Coin Wallet based on automatically generated character seeds
                            </p>
                            <p style="text-align: center;">
                                <a href="#" class="btn btn-primary" onclick="ninja.seeder.seedCount = ninja.seeder.seedLimit; ninja.seeder.seed();">GENERATE WALLET</a>
                            </p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div id="seedpooldisplay"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="menu" class="menu-wrapper">
                <div class="container">
                    <div class="row">
                        <div class="col-12">
                            <ul class="menu" id="mainmenu">
                                <li class="tab selected" id="singlewallet" onclick="ninja.tabSwitch(this);">Single Wallet</li>
                                <li class="tab" id="paperwallet" onclick="ninja.tabSwitch(this);">Paper Wallet</li>
                                <li class="tab" id="bulkwallet" onclick="ninja.tabSwitch(this);" style="display: none;">Bulk Wallet</li>
                                <li class="tab" id="brainwallet" onclick="ninja.tabSwitch(this);">Brain Wallet</li>
                                <li class="tab" id="vanitywallet" onclick="ninja.tabSwitch(this);" style="display: none;">Vanity Wallet</li>
                                <li class="tab" id="splitwallet" onclick="ninja.tabSwitch(this);" style="display: none;">Split Wallet</li>
                                <li class="tab" id="detailwallet" onclick="ninja.tabSwitch(this);">Wallet Details</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="wallets-wrapper">
                <?php
                    $coin = "Ignition Coin";
                    include '../includes/html/wallets.php';
                ?>
            </div>

        </div>
    </div>

    <div class="footer">
        <div class="container">
            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="footer-left">
                        <p class="heading"><strong>Donations:</strong></p>
                        <ul class="donations">
                            <li><strong class="coinname">IC:</strong> <span class="coinaddress">i<strong class="vanity">oLiE48o</strong>Vg1T4X4evxjHCibtEjhVCgKXUV</span></li>
                            <li><strong class="coinname">BTC:</strong> <span class="coinaddress">1<strong class="vanity">oLie</strong>ZkSAuiiWvHsxQNYEaZjMFt3jtcu8</span></li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="footer-right">
                        <div class="logo-footer">
                            <a href="https://ignitioncoin.org" target="_blank"><img src="images/logo-footer.png" alt="IngitionCoin.org"></a>
                        </div>
                        <div class="logo-social">
                            <a href="https://twitter.com/ignition_coin" class="twitter-logo" target="_blank"><img src="images/logo-twitter.png" alt="Follow Ignition Coin on Twitter"></a>
                            <a href="https://discord.io/ignition" class="discord-logo" target="_blank"><img src="images/logo-discord.png" alt="Join the Ignition Coin Discord"></a>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="copyright" style="text-align:center;"><small>forked with love from liteaddress.org. Props to NiNja. JavaScript copyrights are included in the source. <span style="text-decoration:underline;">No warranty.</span></small></div>
                </div>
            </div>
        </div>
    </div>


    <?php
        $paper = true;
        include '../includes/scripts/scripts-footer.php';
    ?>

    <script src="assets/js/dist/wallet.js"></script>
</body>
</html>
