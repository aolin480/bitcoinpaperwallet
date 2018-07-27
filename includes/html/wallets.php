<?php
if (! isset($coin)) {
    $coin = 'Litecoin';
}
?>
<div id="wallets">
    <div id="singlearea" class="walletarea">
        <div class="commands">
            <div id="singlecommands" class="row">
                <span><input type="button" id="newaddress" value="Generate New Address" onclick="ninja.wallets.singlewallet.generateNewAddressAndKey();" /></span>
                <span class="print"><input type="button" name="print" value="Print" id="singleprint" onclick="window.print();" /></span>
            </div>
        </div>
        <div id="keyarea" class="keyarea">
            <div class="public">
                <div class="pubaddress">
                    <span class="label" id="singlelabelbitcoinaddress"><?= $coin; ?> Address</span>
                </div>
                <div id="qrcode_public" class="qrcode_public"></div>
                <div class="pubaddress">
                    <span class="output" id="btcaddress"></span>
                </div>
                <div id="singleshare">SHARE</div>
            </div>
            <div class="private">
                <div class="privwif">
                    <span class="label" id="singlelabelprivatekey">Private Key (Wallet Import Format)</span>
                </div>
                <div id="qrcode_private" class="qrcode_private"></div>
                <div class="privwif">
                    <span class="output" id="btcprivwif"></span>
                </div>
                <div id="singlesecret">SECRET</div>
            </div>
        </div>

        <div id="singlesafety">
            <p id="singletip1"><b>An <?= $coin; ?> wallet</b> is as simple as a single pairing of an <?= $coin; ?> address with its corresponding <?= $coin; ?> private key. Such a wallet has been generated for you in your web browser and is displayed above.</p>
            <p id="singletip2"><b>To safeguard this wallet</b> you must print or otherwise record the <?= $coin; ?> address and private key. It is important to make a backup copy of the private key and store it in a safe location. This site does not have knowledge of your private key. If you are familiar with PGP you can download this all-in-one HTML page and check that you have an authentic version from the author of this site by matching the SHA256 hash of this HTML with the SHA256 hash available in the signed version history document linked on the footer of this site. If you leave/refresh the site or press the "Generate New Address" button then a new private key will be generated and the previously displayed private key will not be retrievable.    Your <?= $coin; ?> private key should be kept a secret. Whomever you share the private key with has access to spend all the <?= $coin . 's'; ?> associated with that address. If you print your wallet then store it in a zip lock bag to keep it safe from water. Treat a paper wallet like cash.</p>
            <p id="singletip3"><b>Add funds</b> to this wallet by instructing others to send <?= $coin . 's'; ?> to your <?= $coin; ?> address.</p>
            <p id="singletip4"><b>Check your balance</b> by going to blockchain.ignitioncoin.org or explorer.ignitioncoin.org and entering your <?= $coin; ?> address.</p>
            <p id="singletip5"><b>Spend your <?= $coin . 's'; ?></b> by downloading and running the latest Ignition Coin wallet (1.1.0.1 and greater) for PC, Mac, or Linux and importing the private key by going to File > Import Private Key to import your Ignition Coin address into the desktop wallet.</p>
        </div>
    </div>

    <div id="paperarea">
        <div class="commands">
            <div id="papercommands" class="row">
                <span><label id="paperlabelhideart" for="paperart">Hide Art?</label> <input type="checkbox" id="paperart" onchange="ninja.wallets.paperwallet.toggleArt(this);" /></span>
                <span><label id="paperlabeladdressestogenerate" for="paperlimit">Addresses to generate:</label> <input type="text" id="paperlimit" /></span>
                <span><input type="button" id="papergenerate" value="Generate" onclick="ninja.wallets.paperwallet.build(document.getElementById('paperlimit').value * 1, document.getElementById('paperlimitperpage').value * 1, !document.getElementById('paperart').checked, document.getElementById('paperpassphrase').value);" /></span>
                <span class="print"><input type="button" name="print" value="Print" id="paperprint" onclick="window.print();" /></span>
            </div>
            <div id="paperadvancedcommands" class="row extra">
                <span><label id="paperlabelencrypt" for="paperencrypt">BIP38 Encrypt?</label> <input type="checkbox" id="paperencrypt" onchange="ninja.wallets.paperwallet.toggleEncrypt(this);" /></span>
                <span><label id="paperlabelBIPpassphrase" for="paperpassphrase">Passphrase:</label> <input type="text" id="paperpassphrase" /></span>
                <span><label id="paperlabeladdressesperpage" for="paperlimitperpage">Addresses per page:</label> <input type="text" id="paperlimitperpage" /></span>
            </div>
        </div>
        <div id="paperkeyarea"></div>
    </div>

    <div id="bulkarea" class="walletarea">
        <div class="commands">
            <div id="bulkcommands" class="row">
                <span><label id="bulklabelstartindex" for="bulkstartindex">Start index:</label> <input type="text" id="bulkstartindex" value="1" /></span>
                <span><label id="bulklabelrowstogenerate" for="bulklimit">Rows to generate:</label> <input type="text" id="bulklimit" value="3" /></span>
                <span><label id="bulklabelcompressed" for="bulkcompressed">Compressed addresses?</label> <input type="checkbox" id="bulkcompressed" /></span>
                <span><input type="button" id="bulkgenerate" value="Generate" onclick="ninja.wallets.bulkwallet.buildCSV(document.getElementById('bulklimit').value * 1, document.getElementById('bulkstartindex').value * 1, document.getElementById('bulkcompressed').checked);" /> </span>
                <span class="print"><input type="button" name="print" id="bulkprint" value="Print" onclick="window.print();" /></span>
            </div>
        </div>
        <div class="body">
            <span class="label" id="bulklabelcsv">Comma Separated Values:</span> <span class="format" id="bulklabelformat">Index,Address,Private Key (WIF)</span>
            <textarea rows="20" cols="88" id="bulktextarea"></textarea>
        </div>
        <div class="faqs">
            <div id="bulkfaq1" class="faq">
                <div id="bulkq1" class="question" onclick="ninja.wallets.bulkwallet.openCloseFaq(1);">
                    <span id="bulklabelq1">Why should I use a Bulk Wallet to accept <?= $coin . 's'; ?> on my website?</span>
                    <div id="bulke1" class="more"></div>
                </div>
                <div id="bulka1" class="answer">The traditional approach to accepting <?= $coin . 's'; ?> on your website requires that you install the official <?= $coin; ?> client daemon ("ignitiond"). Many website hosting packages don't support installing the <?= $coin; ?> daemon. Also, running the <?= $coin; ?> daemon on your web server means your private keys are hosted on the server and could get stolen if your web server is hacked. When using a Bulk Wallet you can upload only the <?= $coin; ?> addresses and not the private keys to your web server. Then you don't have to worry about your <?= $coin; ?> wallet being stolen if your web server is hacked. </div>
            </div>
            <div id="bulkfaq2" class="faq">
                <div id="bulkq2" class="question" onclick="ninja.wallets.bulkwallet.openCloseFaq(2);">
                    <span id="bulklabelq2">How do I use a Bulk Wallet to accept <?= $coin . 's'; ?> on my website?</span>
                    <div id="bulke2" class="more"></div>
                </div>
                <div id="bulka2" class="answer">
                    <ol>
                    <li id="bulklabela2li1">Use the Bulk Wallet tab to pre-generate a large number of <?= $coin; ?> addresses (10,000+). Copy and paste the generated comma separated values (CSV) list to a secure text file on your computer. Backup the file you just created to a secure location.</li>
                    <li id="bulklabela2li2">Import the <?= $coin; ?> addresses into a database table on your web server. (Don't put the wallet/private keys on your web server, otherwise you risk hackers stealing your coins. Just the <?= $coin; ?> addresses as they will be shown to customers.)</li>
                    <li id="bulklabela2li3">Provide an option on your website's shopping cart for your customer to pay in <?= $coin; ?>. When the customer chooses to pay in <?= $coin; ?> you will then display one of the addresses from your database to the customer as his "payment address" and save it with his shopping cart order.</li>
                    <li id="bulklabela2li4">You now need to be notified when the payment arrives. Google "<?= $coin; ?> payment notification" and subscribe to at least one <?= $coin; ?> payment notification service. There are various services that will notify you via Web Services, API, SMS, Email, etc. Once you receive this notification, which could be programmatically automated, you can process the customer's order. To manually check if a payment has arrived you can use Block Explorer. Replace THEADDRESSGOESHERE with the <?= $coin; ?> address you are checking. It could take between 10 minutes to one hour for the transaction to be confirmed.<br />https://blockchain.ignitioncoin.org/address/THEADDRESSGOESHERE<br /><br />Unconfirmed transactions can be viewed at: https://blockchain.ignitioncoin.org/ <br />You should see the transaction there within 30 seconds.</li>
                    <li id="bulklabela2li5"><?= $coin; ?>s will safely pile up on the block chain. Use the original wallet file you generated in step 1 to spend them.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div id="brainarea" class="walletarea">
        <div id="braincommands" class="commands">
            <div class="row">
                <span id="brainlabelenterpassphrase" class="label"><label for="brainpassphrase">Enter Passphrase: </label></span>
                <input tabindex="1" type="password" id="brainpassphrase" value="" onfocus="this.select();" onkeypress="if (event.keyCode == 13) ninja.wallets.brainwallet.view();" />
                <span><label id="brainlabelshow" for="brainpassphraseshow">Show?</label> <input type="checkbox" id="brainpassphraseshow" onchange="ninja.wallets.brainwallet.showToggle(this);" /></span>
                <span class="print"><input type="button" name="print" id="brainprint" value="Print" onclick="window.print();" /></span>
            </div>
            <div class="row extra">
                <span class="label" id="brainlabelconfirm"><label for="brainpassphraseconfirm">Confirm Passphrase: </label></span>
                <input tabindex="2" type="password" id="brainpassphraseconfirm" value="" onfocus="this.select();" onkeypress="if (event.keyCode == 13) ninja.wallets.brainwallet.view();" />
                <span><input tabindex="3" type="button" id="brainview" value="View" onclick="ninja.wallets.brainwallet.view();" /></span>
                <span id="brainalgorithm" class="notes right">Algorithm: SHA256(passphrase)</span>
            </div>
            <div class="row extra"><span id="brainwarning"></span></div>
        </div>
        <div id="brainkeyarea" class="keyarea">
            <div class="public">
                <div id="brainqrcodepublic" class="qrcode_public"></div>
                <div class="pubaddress">
                    <span class="label" id="brainlabelbitcoinaddress"><?= $coin; ?> Address:</span>
                    <span class="output" id="brainbtcaddress"></span>
                </div>
            </div>
            <div class="private">
                <div id="brainqrcodeprivate" class="qrcode_private"></div>
                <div class="privwif">
                    <span class="label" id="brainlabelprivatekey">Private Key (Wallet Import Format):</span>
                    <span class="output" id="brainbtcprivwif"></span>
                </div>
            </div>
        </div>
    </div>

    <div id="vanityarea" class="walletarea">
        <div id="vanitystep1label" class="commands expandable" onclick="ninja.wallets.vanitywallet.openCloseStep(1);">
            <span><label id="vanitylabelstep1">Step 1 - Generate your "Step1 Key Pair"</label> <input type="button" id="vanitynewkeypair"
                value="Generate" onclick="ninja.wallets.vanitywallet.generateKeyPair();" /></span>
            <div id="vanitystep1icon" class="more"></div>
        </div>
        <div id="vanitystep1area">
            <div>
                <span class="label" id="vanitylabelstep1publickey">Step 1 Public Key:</span>
                <div class="output pubkeyhex" id="vanitypubkey"></div>
                <br /><div class="notes" id="vanitylabelstep1pubnotes">Copy and paste the above into the Your-Part-Public-Key field in the Vanity Pool Website.</div>
            </div>
            <div>
                <span class="label" id="vanitylabelstep1privatekey">Step 1 Private Key:</span>
                <span class="output" id="vanityprivatekey"></span>
                <br /><div class="notes" id="vanitylabelstep1privnotes">Copy and paste the above Private Key field into a text file. Ideally save to an encrypted drive. You will need this to retrieve the <?= $coin; ?> Private Key once the Pool has found your prefix.</div>
            </div>
        </div>
        <div id="vanitystep2label" class="expandable" onclick="ninja.wallets.vanitywallet.openCloseStep(2);">
            <span id="vanitylabelstep2calculateyourvanitywallet">Step 2 - Calculate your Vanity Wallet</span>
            <div id="vanitystep2icon" class="more"></div>
        </div>
        <div id="vanitystep2inputs">
            <div>
                <span id="vanitylabelenteryourpart">Enter Your Part Private Key (generated in Step 1 above and previously saved):</span>
                <br /><span class="notes" id="vanitylabelnote1">[NOTE: this input box can accept a public key or private key]</span>
            </div>
            <div><textarea id="vanityinput1" rows="2" cols="90" onfocus="this.select();"></textarea></div>
            <div>
                <span id="vanitylabelenteryourpoolpart">Enter Pool Part Private Key (from Vanity Pool):</span>
                <br /><span class="notes" id="vanitylabelnote2">[NOTE: this input box can accept a public key or private key]</span>
            </div>
            <div><textarea id="vanityinput2" rows="2" cols="90" onfocus="this.select();"></textarea></div>
            <div>
                <label for="vanityradioadd" id="vanitylabelradioadd">Add</label> <input type="radio" id="vanityradioadd" name="vanityradio" value="add" checked />
                <label for="vanityradiomultiply" id="vanitylabelradiomultiply">Multiply</label> <input type="radio" id="vanityradiomultiply" name="vanityradio" value="multiply" />
            </div>
            <div><input type="button" id="vanitycalc" value="Calculate Vanity Wallet" onclick="ninja.wallets.vanitywallet.addKeys();" /></div>
        </div>
        <div id="vanitystep2area">
            <div>
                <span class="label" id="vanitylabelbitcoinaddress">Vanity <?= $coin; ?> Address:</span>
                <span class="output" id="vanityaddress"></span>
                <br /><div class="notes" id="vanitylabelnotesbitcoinaddress">The above is your new address that should include your required prefix.</div>
            </div>

            <div>
                <span class="label" id="vanitylabelpublickeyhex">Vanity Public Key (HEX):</span>
                <span class="output pubkeyhex" id="vanitypublickeyhex"></span>
                <br /><div class="notes" id="vanitylabelnotespublickeyhex">The above is the Public Key in hexadecimal format. </div>
            </div>

            <div>
                <span class="label" id="vanitylabelprivatekey">Vanity Private Key (WIF):</span>
                <span class="output" id="vanityprivatekeywif"></span>
                <br /><div class="notes" id="vanitylabelnotesprivatekey">The above is the Private Key to load into your wallet. </div>
            </div>
        </div>
    </div>

    <div id="splitarea" class="walletarea">
        <div id="splitcommands" class="commands " >
            <label id="splitlabelthreshold">Minimum share threshold needed to combine</label>
            <input type="text" id="splitthreshold" value="2" size="4"/>
            <br/>
            <label id="splitlabelshares">Number of shares</label>
            <input type="text" id="splitshares" value="3" size="4"/>
            <span><input type="button" id="splitview" value="Generate" onclick="ninja.wallets.splitwallet.splitKey();"></span>
            <div id="splitstep1icon" class="more " onclick="ninja.wallets.splitwallet.openCloseStep(1);"></div>
        </div>
        <div id="splitstep1area"></div>

        <div id="combinecommands" class="left commands">
            <span>
                <label id="combinelabelentershares">Enter Available Shares (whitespace separated)</label><br/>
                <textarea id="combineinput" cols="60" rows="10"></textarea><br/>
            </span>
            <span><input type="button" id="combineview" value="Combine Shares" onclick="ninja.wallets.splitwallet.combineShares();"></span>
        </div>
        <div id="splitstep2area">
            <div id="combineoutput">
                <label id="combinelabelprivatekey">Combined Private Key</label>
                <div id="combinedprivatekey" class="output"></div>
            </div>
        </div>
    </div>

    <div id="detailarea" class="walletarea">
        <div id="detailcommands" class="commands">
            <span><label id="detaillabelenterprivatekey" for="detailprivkey">Enter Private Key</label></span>
            <input type="text" id="detailprivkey" value="" onfocus="this.select();" onkeypress="if (event.keyCode == 13) ninja.wallets.detailwallet.viewDetails();" />
            <span><input type="button" id="detailview" value="View Details" onclick="ninja.wallets.detailwallet.viewDetails();" /></span>
            <span class="print"><input type="button" name="print" id="detailprint" value="Print" onclick="window.print();" /></span>
            <div class="row extra">
                <span><label id="detailkeyformats">Key Formats: WIF, WIFC, HEX, B64, B6, MINI, BIP38</label></span>
            </div>
            <div id="detailbip38commands">
                <span><label id="detaillabelpassphrase">Enter BIP38 Passphrase</label> <input type="text" id="detailprivkeypassphrase" value="" onfocus="this.select();" onkeypress="if (event.keyCode == 13) ninja.wallets.detailwallet.viewDetails();" /></span>
                <span><input type="button" id="detaildecrypt" value="Decrypt BIP38" onclick="ninja.wallets.detailwallet.viewDetails();" /></span>
            </div>
        </div>
        <div id="detailkeyarea">
            <div class="notes">
                <span id="detaillabelnote1">Your <?= $coin; ?> Private Key is a unique secret number that only you know. It can be encoded in a number of different formats. Below we show the <?= $coin; ?> Address and Public Key that corresponds to your Private Key as well as your Private Key in the most popular encoding formats (WIF, WIFC, HEX, B64).</span>
                <br /><br />
                <span id="detaillabelnote2"><?= $coin; ?> v0.6+ stores public keys in compressed format. The client now also supports import and export of private keys with importprivkey/dumpprivkey. The format of the exported private key is determined by whether the address was generated in an old or new wallet.</span>
            </div>
            <div class="pubqr">
                <div class="item">
                    <span class="label" id="detaillabelbitcoinaddress"><?= $coin; ?> Address</span>
                    <div id="detailqrcodepublic" class="qrcode_public"></div>
                    <span class="output" id="detailaddress"></span>
                </div>
                <div class="item right">
                    <span class="label" id="detaillabelbitcoinaddresscomp"><?= $coin; ?> Address Compressed</span>
                    <div id="detailqrcodepubliccomp" class="qrcode_public"></div>
                    <span class="output" id="detailaddresscomp"></span>
                </div>
            </div>
            <br /><br />
            <div class="item clear">
                <span class="label" id="detaillabelpublickey">Public Key (130 characters [0-9A-F]):</span>
                <span class="output pubkeyhex" id="detailpubkey"></span>
            </div>
            <div class="item">
                <span class="label" id="detaillabelpublickeycomp">Public Key (compressed, 66 characters [0-9A-F]):</span>
                <span class="output" id="detailpubkeycomp"></span>
            </div>
            <hr />
            <div class="privqr">
                <div class="item">
                    <span class="label"><span id="detaillabelprivwif">Private Key WIF<br />51 characters base58, starts with a</span> <span id="detailwifprefix">'5'</span></span>
                    <div id="detailqrcodeprivate" class="qrcode_private"></div>
                    <span class="output" id="detailprivwif"></span>
                </div>
                <div class="item right">
                    <span class="label"><span id="detaillabelprivwifcomp">Private Key WIF Compressed<br />52 characters base58, starts with a</span> <span id="detailcompwifprefix">'T'</span></span>
                    <div id="detailqrcodeprivatecomp" class="qrcode_private"></div>
                    <span class="output" id="detailprivwifcomp"></span>
                </div>
            </div>
            <br /><br />
            <div class="item clear">
                <span class="label" id="detaillabelprivhex">Private Key Hexadecimal Format (64 characters [0-9A-F]):</span>
                <span class="output" id="detailprivhex"></span>
            </div>
            <div class="item">
                <span class="label" id="detaillabelprivb64">Private Key Base64 (44 characters):</span>
                <span class="output" id="detailprivb64"></span>
            </div>
            <div class="item" style="display: none;" id="detailmini">
                <span class="label" id="detaillabelprivmini">Private Key Mini Format (22, 26 or 30 characters, starts with an 'S'):</span>
                <span class="output" id="detailprivmini"></span>
            </div>
            <div class="item" style="display: none;" id="detailb6">
                <span class="label" id="detaillabelprivb6">Private Key Base6 Format (99 characters [0-5]):</span>
                <span class="output" id="detailprivb6"></span>
            </div>
            <div class="item" style="display: none;" id="detailbip38">
                <span class="label" id="detaillabelprivbip38">Private Key BIP38 Format (58 characters base58, starts with '6P'):</span>
                <span class="output" id="detailprivbip38"></span>
            </div>
        </div>
        <div class="faqs">
            <div id="detailfaq1" class="faq">
                <div id="detailq1" class="question" onclick="ninja.wallets.detailwallet.openCloseFaq(1);">
                    <span id="detaillabelq1">How do I make a wallet using dice? What is B6?</span>
                    <div id="detaile1" class="more"></div>
                </div>
                <div id="detaila1" class="answer">An important part of creating an <?= $coin; ?> wallet is ensuring the random numbers used to create the wallet are truly random. Physical randomness is better than computer generated pseudo-randomness. The easiest way to generate physical randomness is with dice. To create an <?= $coin; ?> private key you only need one six sided die which you roll 99 times. Stopping each time to record the value of the die. When recording the values follow these rules: 1=1, 2=2, 3=3, 4=4, 5=5, 6=0. By doing this you are recording the big random number, your private key, in B6 or base 6 format. You can then enter the 99 character base 6 private key into the text field above and click View Details. You will then see the <?= $coin; ?> address associated with your private key. You should also make note of your private key in WIF format since it is more widely used.</div>
            </div>
        </div>
    </div>
</div>
