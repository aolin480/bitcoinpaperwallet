<!doctype html>
<html>
<head>
    <!--
    Donation Address: MHg7DcNPhpCqTxijJDmAyA2U3zjj475uf8

    Notice of Copyrights and Licenses:
    ***********************************
    The bitaddress.org project, software and embedded resources are copyright bitaddress.org (pointbiz).
    The bitaddress.org name and logo are not part of the open source license.

    Portions of the all-in-one HTML document contain JavaScript codes that are the copyrights of others.
    The individual copyrights are included throughout the document along with their licenses.
    Included JavaScript libraries are separated with HTML script tags.

    Summary of JavaScript functions with a redistributable license:
    JavaScript function     License
    *******************     ***************
    Array.prototype.map     Public Domain
    window.Crypto           BSD License
    window.SecureRandom     BSD License
    window.EllipticCurve        BSD License
    window.BigInteger       BSD License
    window.QRCode           MIT License
    window.Litecoin         MIT License
    window.Crypto_scrypt        MIT License

    The bitaddress.org software is available under The MIT License (MIT)
    Copyright (c) 2011-2013 bitaddress.org (pointbiz)

    Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
    associated documentation files (the "Software"), to deal in the Software without restriction, including
    without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
    sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject
    to the following conditions:

    The above copyright notice and this permission notice shall be included in all copies or substantial
    portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT
    LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
    IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
    WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
    SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

    GitHub Repository: https://github.com/pointbiz/bitaddress.org
    -->

    <title>liteaddress.org</title>
    <meta charset="utf-8">


<?php include 'includes/scripts/scripts-header.php'; ?>
<?php include "includes/styles.php"; ?>

</head>
<body onclick="SecureRandom.seedTime();" onmousemove="ninja.seeder.seed(event);">
	<div id="busyblock"></div>
	<div id="main">
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
		<img alt="liteaddress.org" title="liteaddress.org" id="logo" src="images/logo.png" />
		<div id="tagline">Open Source JavaScript Client-Side Litecoin Wallet Generator</div>
		<div id="seedpoolarea"><textarea rows="16" cols="62" id="seedpool"></textarea></div>

		<div id="testnet"></div>

		<ul class="menu" id="menu">
			<li class="tab selected" id="singlewallet" onclick="ninja.tabSwitch(this);">Single Wallet
			<li class="tab" id="paperwallet" onclick="ninja.tabSwitch(this);">Paper Wallet
			<li class="tab" id="bulkwallet" onclick="ninja.tabSwitch(this);">Bulk Wallet
			<li class="tab" id="brainwallet" onclick="ninja.tabSwitch(this);">Brain Wallet
			<li class="tab" id="vanitywallet" onclick="ninja.tabSwitch(this);">Vanity Wallet
			<li class="tab" id="splitwallet" onclick="ninja.tabSwitch(this);">Split Wallet
			<li class="tab" id="detailwallet" onclick="ninja.tabSwitch(this);">Wallet Details
		</ul>

		<div id="generate">
			<span id="generatelabelbitcoinaddress">Generating Litecoin Address...</span><br />
			<span id="generatelabelmovemouse">MOVE your mouse around to add some extra randomness... </span><span id="mousemovelimit"></span><br />
			<span id="generatelabelkeypress">OR type some random characters into this textbox</span> <input type="text" id="generatekeyinput" onkeydown="ninja.seeder.seedKeyPress(event);" /><br />
			<div id="seedpooldisplay"></div>
		</div>

		<?php include 'includes/html/wallets.php'; ?>
		<?php include 'includes/html/footer.php'; ?>
	</div>

<?php include 'includes/scripts/scripts-footer.php'; ?>

</body>
</html>
