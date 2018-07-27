
    <script type="text/javascript">
var ninja = { wallets: {} };

ninja.privateKey = {
    isPrivateKey: function (key) {
        return (
                    Bitcoin.ECKey.isWalletImportFormat(key) ||
                    Bitcoin.ECKey.isCompressedWalletImportFormat(key) ||
                    Bitcoin.ECKey.isHexFormat(key) ||
                    Bitcoin.ECKey.isBase64Format(key) ||
                    Bitcoin.ECKey.isMiniFormat(key)
                );
    },
    getECKeyFromAdding: function (privKey1, privKey2) {
        var n = EllipticCurve.getSECCurveByName("secp256k1").getN();
        var ecKey1 = new Bitcoin.ECKey(privKey1);
        var ecKey2 = new Bitcoin.ECKey(privKey2);
        // if both keys are the same return null
        if (ecKey1.getBitcoinHexFormat() == ecKey2.getBitcoinHexFormat()) return null;
        if (ecKey1 == null || ecKey2 == null) return null;
        var combinedPrivateKey = new Bitcoin.ECKey(ecKey1.priv.add(ecKey2.priv).mod(n));
        // compressed when both keys are compressed
        if (ecKey1.compressed && ecKey2.compressed) combinedPrivateKey.setCompressed(true);
        return combinedPrivateKey;
    },
    getECKeyFromMultiplying: function (privKey1, privKey2) {
        var n = EllipticCurve.getSECCurveByName("secp256k1").getN();
        var ecKey1 = new Bitcoin.ECKey(privKey1);
        var ecKey2 = new Bitcoin.ECKey(privKey2);
        // if both keys are the same return null
        if (ecKey1.getBitcoinHexFormat() == ecKey2.getBitcoinHexFormat()) return null;
        if (ecKey1 == null || ecKey2 == null) return null;
        var combinedPrivateKey = new Bitcoin.ECKey(ecKey1.priv.multiply(ecKey2.priv).mod(n));
        // compressed when both keys are compressed
        if (ecKey1.compressed && ecKey2.compressed) combinedPrivateKey.setCompressed(true);
        return combinedPrivateKey;
    },
    // 58 base58 characters starting with 6P
    isBIP38Format: function (key) {
        key = key.toString();
        return (/^6P[123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz]{56}$/.test(key));
    },
    BIP38EncryptedKeyToByteArrayAsync: function (base58Encrypted, passphrase, callback) {
        var hex;
        try {
            hex = Bitcoin.Base58.decode(base58Encrypted);
        } catch (e) {
            callback(new Error(ninja.translator.get("detailalertnotvalidprivatekey")));
            return;
        }

        // 43 bytes: 2 bytes prefix, 37 bytes payload, 4 bytes checksum
        if (hex.length != 43) {
            callback(new Error(ninja.translator.get("detailalertnotvalidprivatekey")));
            return;
        }
        // first byte is always 0x01
        else if (hex[0] != 0x01) {
            callback(new Error(ninja.translator.get("detailalertnotvalidprivatekey")));
            return;
        }

        var expChecksum = hex.slice(-4);
        hex = hex.slice(0, -4);
        var checksum = Bitcoin.Util.dsha256(hex);
        if (checksum[0] != expChecksum[0] || checksum[1] != expChecksum[1] || checksum[2] != expChecksum[2] || checksum[3] != expChecksum[3]) {
            callback(new Error(ninja.translator.get("detailalertnotvalidprivatekey")));
            return;
        }

        var isCompPoint = false;
        var isECMult = false;
        var hasLotSeq = false;
        // second byte for non-EC-multiplied key
        if (hex[1] == 0x42) {
            // key should use compression
            if (hex[2] == 0xe0) {
                isCompPoint = true;
            }
            // key should NOT use compression
            else if (hex[2] != 0xc0) {
                callback(new Error(ninja.translator.get("detailalertnotvalidprivatekey")));
                return;
            }
        }
        // second byte for EC-multiplied key
        else if (hex[1] == 0x43) {
            isECMult = true;
            isCompPoint = (hex[2] & 0x20) != 0;
            hasLotSeq = (hex[2] & 0x04) != 0;
            if ((hex[2] & 0x24) != hex[2]) {
                callback(new Error(ninja.translator.get("detailalertnotvalidprivatekey")));
                return;
            }
        }
        else {
            callback(new Error(ninja.translator.get("detailalertnotvalidprivatekey")));
            return;
        }

        var decrypted;
        var AES_opts = { mode: new Crypto.mode.ECB(Crypto.pad.NoPadding), asBytes: true };

        var verifyHashAndReturn = function () {
            var tmpkey = new Bitcoin.ECKey(decrypted); // decrypted using closure
            var base58AddrText = tmpkey.setCompressed(isCompPoint).getBitcoinAddress(); // isCompPoint using closure
            checksum = Bitcoin.Util.dsha256(base58AddrText); // checksum using closure

            if (checksum[0] != hex[3] || checksum[1] != hex[4] || checksum[2] != hex[5] || checksum[3] != hex[6]) {
                callback(new Error(ninja.translator.get("bip38alertincorrectpassphrase"))); // callback using closure
                return;
            }
            callback(tmpkey.getBitcoinPrivateKeyByteArray()); // callback using closure
        };

        if (!isECMult) {
            var addresshash = hex.slice(3, 7);
            Crypto_scrypt(passphrase, addresshash, 16384, 8, 8, 64, function (derivedBytes) {
                var k = derivedBytes.slice(32, 32 + 32);
                decrypted = Crypto.AES.decrypt(hex.slice(7, 7 + 32), k, AES_opts);
                for (var x = 0; x < 32; x++) decrypted[x] ^= derivedBytes[x];
                verifyHashAndReturn(); //TODO: pass in 'decrypted' as a param
            });
        }
        else {
            var ownerentropy = hex.slice(7, 7 + 8);
            var ownersalt = !hasLotSeq ? ownerentropy : ownerentropy.slice(0, 4);
            Crypto_scrypt(passphrase, ownersalt, 16384, 8, 8, 32, function (prefactorA) {
                var passfactor;
                if (!hasLotSeq) { // hasLotSeq using closure
                    passfactor = prefactorA;
                } else {
                    var prefactorB = prefactorA.concat(ownerentropy); // ownerentropy using closure
                    passfactor = Bitcoin.Util.dsha256(prefactorB);
                }
                var kp = new Bitcoin.ECKey(passfactor);
                var passpoint = kp.setCompressed(true).getPub();

                var encryptedpart2 = hex.slice(23, 23 + 16);

                var addresshashplusownerentropy = hex.slice(3, 3 + 12);
                Crypto_scrypt(passpoint, addresshashplusownerentropy, 1024, 1, 1, 64, function (derived) {
                    var k = derived.slice(32);

                    var unencryptedpart2 = Crypto.AES.decrypt(encryptedpart2, k, AES_opts);
                    for (var i = 0; i < 16; i++) { unencryptedpart2[i] ^= derived[i + 16]; }

                    var encryptedpart1 = hex.slice(15, 15 + 8).concat(unencryptedpart2.slice(0, 0 + 8));
                    var unencryptedpart1 = Crypto.AES.decrypt(encryptedpart1, k, AES_opts);
                    for (var i = 0; i < 16; i++) { unencryptedpart1[i] ^= derived[i]; }

                    var seedb = unencryptedpart1.slice(0, 0 + 16).concat(unencryptedpart2.slice(8, 8 + 8));

                    var factorb = Bitcoin.Util.dsha256(seedb);

                    var ps = EllipticCurve.getSECCurveByName("secp256k1");
                    var privateKey = BigInteger.fromByteArrayUnsigned(passfactor).multiply(BigInteger.fromByteArrayUnsigned(factorb)).remainder(ps.getN());

                    decrypted = privateKey.toByteArrayUnsigned();
                    verifyHashAndReturn();
                });
            });
        }
    },
    BIP38PrivateKeyToEncryptedKeyAsync: function (base58Key, passphrase, compressed, callback) {
        var privKey = new Bitcoin.ECKey(base58Key);
        var privKeyBytes = privKey.getBitcoinPrivateKeyByteArray();
        var address = privKey.setCompressed(compressed).getBitcoinAddress();

        // compute sha256(sha256(address)) and take first 4 bytes
        var salt = Bitcoin.Util.dsha256(address).slice(0, 4);

        // derive key using scrypt
        var AES_opts = { mode: new Crypto.mode.ECB(Crypto.pad.NoPadding), asBytes: true };

        Crypto_scrypt(passphrase, salt, 16384, 8, 8, 64, function (derivedBytes) {
            for (var i = 0; i < 32; ++i) {
                privKeyBytes[i] ^= derivedBytes[i];
            }

            // 0x01 0x42 + flagbyte + salt + encryptedhalf1 + encryptedhalf2
            var flagByte = compressed ? 0xe0 : 0xc0;
            var encryptedKey = [0x01, 0x42, flagByte].concat(salt);
            encryptedKey = encryptedKey.concat(Crypto.AES.encrypt(privKeyBytes, derivedBytes.slice(32), AES_opts));
            encryptedKey = encryptedKey.concat(Bitcoin.Util.dsha256(encryptedKey).slice(0, 4));
            callback(Bitcoin.Base58.encode(encryptedKey));
        });
    },
    BIP38GenerateIntermediatePointAsync: function (passphrase, lotNum, sequenceNum, callback) {
        var noNumbers = lotNum === null || sequenceNum === null;
        var rng = new SecureRandom();
        var ownerEntropy, ownerSalt;

        if (noNumbers) {
            ownerSalt = ownerEntropy = new Array(8);
            rng.nextBytes(ownerEntropy);
        }
        else {
            // 1) generate 4 random bytes
            ownerSalt = new Array(4);

            rng.nextBytes(ownerSalt);

            // 2)  Encode the lot and sequence numbers as a 4 byte quantity (big-endian):
            // lotnumber * 4096 + sequencenumber. Call these four bytes lotsequence.
            var lotSequence = BigInteger(4096 * lotNum + sequenceNum).toByteArrayUnsigned();

            // 3) Concatenate ownersalt + lotsequence and call this ownerentropy.
            var ownerEntropy = ownerSalt.concat(lotSequence);
        }


        // 4) Derive a key from the passphrase using scrypt
        Crypto_scrypt(passphrase, ownerSalt, 16384, 8, 8, 32, function (prefactor) {
            // Take SHA256(SHA256(prefactor + ownerentropy)) and call this passfactor
            var passfactorBytes = noNumbers ? prefactor : Bitcoin.Util.dsha256(prefactor.concat(ownerEntropy));
            var passfactor = BigInteger.fromByteArrayUnsigned(passfactorBytes);

            // 5) Compute the elliptic curve point G * passfactor, and convert the result to compressed notation (33 bytes)
            var ellipticCurve = EllipticCurve.getSECCurveByName("secp256k1");
            var passpoint = ellipticCurve.getG().multiply(passfactor).getEncoded(1);

            // 6) Convey ownersalt and passpoint to the party generating the keys, along with a checksum to ensure integrity.
            // magic bytes "2C E9 B3 E1 FF 39 E2 51" followed by ownerentropy, and then passpoint
            var magicBytes = [0x2C, 0xE9, 0xB3, 0xE1, 0xFF, 0x39, 0xE2, 0x51];
            if (noNumbers) magicBytes[7] = 0x53;

            var intermediate = magicBytes.concat(ownerEntropy).concat(passpoint);

            // base58check encode
            intermediate = intermediate.concat(Bitcoin.Util.dsha256(intermediate).slice(0, 4));
            callback(Bitcoin.Base58.encode(intermediate));
        });
    },
    BIP38GenerateECAddressAsync: function (intermediate, compressed, callback) {
        // decode IPS
        var x = Bitcoin.Base58.decode(intermediate);
        //if(x.slice(49, 4) !== Bitcoin.Util.dsha256(x.slice(0,49)).slice(0,4)) {
        //  callback({error: 'Invalid intermediate passphrase string'});
        //}
        var noNumbers = (x[7] === 0x53);
        var ownerEntropy = x.slice(8, 8 + 8);
        var passpoint = x.slice(16, 16 + 33);

        // 1) Set flagbyte.
        // set bit 0x20 for compressed key
        // set bit 0x04 if ownerentropy contains a value for lotsequence
        var flagByte = (compressed ? 0x20 : 0x00) | (noNumbers ? 0x00 : 0x04);


        // 2) Generate 24 random bytes, call this seedb.
        var seedB = new Array(24);
        var rng = new SecureRandom();
        rng.nextBytes(seedB);

        // Take SHA256(SHA256(seedb)) to yield 32 bytes, call this factorb.
        var factorB = Bitcoin.Util.dsha256(seedB);

        // 3) ECMultiply passpoint by factorb. Use the resulting EC point as a public key and hash it into a Bitcoin
        // address using either compressed or uncompressed public key methodology (specify which methodology is used
        // inside flagbyte). This is the generated Bitcoin address, call it generatedaddress.
        var ec = EllipticCurve.getSECCurveByName("secp256k1").getCurve();
        var generatedPoint = ec.decodePointHex(ninja.publicKey.getHexFromByteArray(passpoint));
        var generatedBytes = generatedPoint.multiply(BigInteger.fromByteArrayUnsigned(factorB)).getEncoded(compressed);
        var generatedAddress = (new Bitcoin.Address(Bitcoin.Util.sha256ripe160(generatedBytes))).toString();

        // 4) Take the first four bytes of SHA256(SHA256(generatedaddress)) and call it addresshash.
        var addressHash = Bitcoin.Util.dsha256(generatedAddress).slice(0, 4);

        // 5) Now we will encrypt seedb. Derive a second key from passpoint using scrypt
        Crypto_scrypt(passpoint, addressHash.concat(ownerEntropy), 1024, 1, 1, 64, function (derivedBytes) {
            // 6) Do AES256Encrypt(seedb[0...15]] xor derivedhalf1[0...15], derivedhalf2), call the 16-byte result encryptedpart1
            for (var i = 0; i < 16; ++i) {
                seedB[i] ^= derivedBytes[i];
            }
            var AES_opts = { mode: new Crypto.mode.ECB(Crypto.pad.NoPadding), asBytes: true };
            var encryptedPart1 = Crypto.AES.encrypt(seedB.slice(0, 16), derivedBytes.slice(32), AES_opts);

            // 7) Do AES256Encrypt((encryptedpart1[8...15] + seedb[16...23]) xor derivedhalf1[16...31], derivedhalf2), call the 16-byte result encryptedseedb.
            var message2 = encryptedPart1.slice(8, 8 + 8).concat(seedB.slice(16, 16 + 8));
            for (var i = 0; i < 16; ++i) {
                message2[i] ^= derivedBytes[i + 16];
            }
            var encryptedSeedB = Crypto.AES.encrypt(message2, derivedBytes.slice(32), AES_opts);

            // 0x01 0x43 + flagbyte + addresshash + ownerentropy + encryptedpart1[0...7] + encryptedpart2
            var encryptedKey = [0x01, 0x43, flagByte].concat(addressHash).concat(ownerEntropy).concat(encryptedPart1.slice(0, 8)).concat(encryptedSeedB);

            // base58check encode
            encryptedKey = encryptedKey.concat(Bitcoin.Util.dsha256(encryptedKey).slice(0, 4));
            callback(generatedAddress, Bitcoin.Base58.encode(encryptedKey));
        });
    }
};

ninja.publicKey = {
    isPublicKeyHexFormat: function (key) {
        key = key.toString();
        return ninja.publicKey.isUncompressedPublicKeyHexFormat(key) || ninja.publicKey.isCompressedPublicKeyHexFormat(key);
    },
    // 130 characters [0-9A-F] starts with 04
    isUncompressedPublicKeyHexFormat: function (key) {
        key = key.toString();
        return /^04[A-Fa-f0-9]{128}$/.test(key);
    },
    // 66 characters [0-9A-F] starts with 02 or 03
    isCompressedPublicKeyHexFormat: function (key) {
        key = key.toString();
        return /^0[2-3][A-Fa-f0-9]{64}$/.test(key);
    },
    getBitcoinAddressFromByteArray: function (pubKeyByteArray) {
        var pubKeyHash = Bitcoin.Util.sha256ripe160(pubKeyByteArray);
        var addr = new Bitcoin.Address(pubKeyHash);
        return addr.toString();
    },
    getHexFromByteArray: function (pubKeyByteArray) {
        return Crypto.util.bytesToHex(pubKeyByteArray).toString().toUpperCase();
    },
    getByteArrayFromAdding: function (pubKeyHex1, pubKeyHex2) {
        var ecparams = EllipticCurve.getSECCurveByName("secp256k1");
        var curve = ecparams.getCurve();
        var ecPoint1 = curve.decodePointHex(pubKeyHex1);
        var ecPoint2 = curve.decodePointHex(pubKeyHex2);
        // if both points are the same return null
        if (ecPoint1.equals(ecPoint2)) return null;
        var compressed = (ecPoint1.compressed && ecPoint2.compressed);
        var pubKey = ecPoint1.add(ecPoint2).getEncoded(compressed);
        return pubKey;
    },
    getByteArrayFromMultiplying: function (pubKeyHex, ecKey) {
        var ecparams = EllipticCurve.getSECCurveByName("secp256k1");
        var ecPoint = ecparams.getCurve().decodePointHex(pubKeyHex);
        var compressed = (ecPoint.compressed && ecKey.compressed);
        // if both points are the same return null
        ecKey.setCompressed(false);
        if (ecPoint.equals(ecKey.getPubPoint())) {
            return null;
        }
        var bigInt = ecKey.priv;
        var pubKey = ecPoint.multiply(bigInt).getEncoded(compressed);
        return pubKey;
    },
    // used by unit test
    getDecompressedPubKeyHex: function (pubKeyHexComp) {
        var ecparams = EllipticCurve.getSECCurveByName("secp256k1");
        var ecPoint = ecparams.getCurve().decodePointHex(pubKeyHexComp);
        var pubByteArray = ecPoint.getEncoded(0);
        var pubHexUncompressed = ninja.publicKey.getHexFromByteArray(pubByteArray);
        return pubHexUncompressed;
    }
};
    </script>

    <script type="text/javascript">
ninja.seeder = {
    init: (function () {
        document.getElementById("generatekeyinput").value = "";
    })(),

    // number of mouse movements to wait for
    seedLimit: (function () {
        var num = Crypto.util.randomBytes(12)[11];
        return 10;
        return 200 + Math.floor(num);
    })(),

    seedCount: 0, // counter
    lastInputTime: new Date().getTime(),
    seedPoints: [],

    // seed function exists to wait for mouse movement to add more entropy before generating an address
    seed: function (evt) {

        if (!evt) var evt = window.event;
        var timeStamp = new Date().getTime();
        // seeding is over now we generate and display the address
        if (ninja.seeder.seedCount == ninja.seeder.seedLimit) {
            ninja.seeder.seedCount++;
            ninja.wallets.singlewallet.open();
            document.getElementById("generate").style.display = "none";
            document.getElementById("menu").style.visibility = "visible";
            ninja.seeder.removePoints();
        }
        // seed mouse position X and Y when mouse movements are greater than 40ms apart.
        else if ((ninja.seeder.seedCount < ninja.seeder.seedLimit) && evt && (timeStamp - ninja.seeder.lastInputTime) > 40) {
            SecureRandom.seedTime();
            SecureRandom.seedInt16((evt.clientX * evt.clientY));
            ninja.seeder.showPoint(evt.clientX, evt.clientY);
            ninja.seeder.seedCount++;
            ninja.seeder.lastInputTime = new Date().getTime();
            ninja.seeder.showPool();
        }
    },

    // seed function exists to wait for mouse movement to add more entropy before generating an address
    seedKeyPress: function (evt) {
        if (!evt) var evt = window.event;
        // seeding is over now we generate and display the address
        if (ninja.seeder.seedCount == ninja.seeder.seedLimit) {
            ninja.seeder.seedCount++;
            ninja.wallets.singlewallet.open();
            document.getElementById("generate").style.display = "none";
            document.getElementById("menu").style.visibility = "visible";
            ninja.seeder.removePoints();
        }
        // seed key press character
        else if ((ninja.seeder.seedCount < ninja.seeder.seedLimit) && evt.which) {
            var timeStamp = new Date().getTime();
            // seed a bunch (minimum seedLimit) of times
            SecureRandom.seedTime();
            SecureRandom.seedInt8(evt.which);
            var keyPressTimeDiff = timeStamp - ninja.seeder.lastInputTime;
            SecureRandom.seedInt8(keyPressTimeDiff);
            ninja.seeder.seedCount++;
            ninja.seeder.lastInputTime = new Date().getTime();
            ninja.seeder.showPool();
        }
    },

    showPool: function () {
        var poolHex;
        if (SecureRandom.poolCopyOnInit != null) {
            poolHex = Crypto.util.bytesToHex(SecureRandom.poolCopyOnInit);
            document.getElementById("seedpool").innerHTML = poolHex;
            document.getElementById("seedpooldisplay").innerHTML = poolHex;
        }
        else {
            poolHex = Crypto.util.bytesToHex(SecureRandom.pool);
            document.getElementById("seedpool").innerHTML = poolHex;
            document.getElementById("seedpooldisplay").innerHTML = poolHex;
        }
        document.getElementById("mousemovelimit").innerHTML = (ninja.seeder.seedLimit - ninja.seeder.seedCount);
    },

    showPoint: function (x, y) {
        var div = document.createElement("div");
        div.setAttribute("class", "seedpoint");
        div.style.top = y + "px";
        div.style.left = x + "px";
        document.body.appendChild(div);
        ninja.seeder.seedPoints.push(div);
    },

    removePoints: function () {
        for (var i = 0; i < ninja.seeder.seedPoints.length; i++) {
            document.body.removeChild(ninja.seeder.seedPoints[i]);
        }
        ninja.seeder.seedPoints = [];
    }
};

ninja.qrCode = {
    // determine which type number is big enough for the input text length
    getTypeNumber: function (text) {
        var lengthCalculation = text.length * 8 + 12; // length as calculated by the QRCode
        if (lengthCalculation < 72) { return 1; }
        else if (lengthCalculation < 128) { return 2; }
        else if (lengthCalculation < 208) { return 3; }
        else if (lengthCalculation < 288) { return 4; }
        else if (lengthCalculation < 368) { return 5; }
        else if (lengthCalculation < 480) { return 6; }
        else if (lengthCalculation < 528) { return 7; }
        else if (lengthCalculation < 688) { return 8; }
        else if (lengthCalculation < 800) { return 9; }
        else if (lengthCalculation < 976) { return 10; }
        return null;
    },

    createCanvas: function (text, sizeMultiplier) {
        sizeMultiplier = (sizeMultiplier == undefined) ? 2 : sizeMultiplier; // default 2
        // create the qrcode itself
        var typeNumber = ninja.qrCode.getTypeNumber(text);
        var qrcode = new QRCode(typeNumber, QRCode.ErrorCorrectLevel.H);
        qrcode.addData(text);
        qrcode.make();
        var width = qrcode.getModuleCount() * sizeMultiplier;
        var height = qrcode.getModuleCount() * sizeMultiplier;
        // create canvas element
        var canvas = document.createElement('canvas');
        var scale = 10.0;
        canvas.width = width * scale;
        canvas.height = height * scale;
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';
        var ctx = canvas.getContext('2d');
        ctx.scale(scale, scale);
        // compute tileW/tileH based on width/height
        var tileW = width / qrcode.getModuleCount();
        var tileH = height / qrcode.getModuleCount();
        // draw in the canvas
        for (var row = 0; row < qrcode.getModuleCount(); row++) {
            for (var col = 0; col < qrcode.getModuleCount(); col++) {
                ctx.fillStyle = qrcode.isDark(row, col) ? "#000000" : "#ffffff";
                ctx.fillRect(col * tileW, row * tileH, tileW, tileH);
            }
        }
        // return just built canvas
        return canvas;
    },

    // generate a QRCode and return it's representation as an Html table
    createTableHtml: function (text) {
        var typeNumber = ninja.qrCode.getTypeNumber(text);
        var qr = new QRCode(typeNumber, QRCode.ErrorCorrectLevel.H);
        qr.addData(text);
        qr.make();
        var tableHtml = "<table class='qrcodetable'>";
        for (var r = 0; r < qr.getModuleCount(); r++) {
            tableHtml += "<tr>";
            for (var c = 0; c < qr.getModuleCount(); c++) {
                if (qr.isDark(r, c)) {
                    tableHtml += "<td class='qrcodetddark'/>";
                } else {
                    tableHtml += "<td class='qrcodetdlight'/>";
                }
            }
            tableHtml += "</tr>";
        }
        tableHtml += "</table>";
        return tableHtml;
    },

    // show QRCodes with canvas OR table (IE8)
    // parameter: keyValuePair
    // example: { "id1": "string1", "id2": "string2"}
    //      "id1" is the id of a div element where you want a QRCode inserted.
    //      "string1" is the string you want encoded into the QRCode.
    showQrCode: function (keyValuePair, sizeMultiplier) {
        for (var key in keyValuePair) {
            var value = keyValuePair[key];
            try {
                if (document.getElementById(key)) {
                    document.getElementById(key).innerHTML = "";
                    document.getElementById(key).appendChild(ninja.qrCode.createCanvas(value, sizeMultiplier));
                }
            }
            catch (e) {
                // for browsers that do not support canvas (IE8)
                document.getElementById(key).innerHTML = ninja.qrCode.createTableHtml(value);
            }
        }
    }
};

ninja.tabSwitch = function (walletTab) {
    if (walletTab.className.indexOf("selected") == -1) {
        // unselect all tabs
        for (var wType in ninja.wallets) {
            document.getElementById(wType).className = "tab";
            ninja.wallets[wType].close();
        }
        walletTab.className += " selected";
        ninja.wallets[walletTab.getAttribute("id")].open();
    }
};

ninja.getQueryString = function () {
    var result = {}, queryString = location.search.substring(1), re = /([^&=]+)=([^&]*)/g, m;
    while (m = re.exec(queryString)) {
        result[decodeURIComponent(m[1])] = decodeURIComponent(m[2]);
    }
    return result;
};

// use when passing an Array of Functions
ninja.runSerialized = function (functions, onComplete) {
    onComplete = onComplete || function () { };

    if (functions.length === 0) onComplete();
    else {
        // run the first function, and make it call this
        // function when finished with the rest of the list
        var f = functions.shift();
        f(function () { ninja.runSerialized(functions, onComplete); });
    }
};

ninja.forSerialized = function (initial, max, whatToDo, onComplete) {
    onComplete = onComplete || function () { };

    if (initial === max) { onComplete(); }
    else {
        // same idea as runSerialized
        whatToDo(initial, function () { ninja.forSerialized(++initial, max, whatToDo, onComplete); });
    }
};

// use when passing an Object (dictionary) of Functions
ninja.foreachSerialized = function (collection, whatToDo, onComplete) {
    var keys = [];
    for (var name in collection) {
        keys.push(name);
    }
    ninja.forSerialized(0, keys.length, function (i, callback) {
        whatToDo(keys[i], callback);
    }, onComplete);
};
    </script>

<?php
if (isset($paper)) {
    include '../includes/ninja/translator.php';
} else {
    include 'includes/ninja/translator.php';
}
?>

    <script type="text/javascript">
ninja.wallets.singlewallet = {
    open: function () {
        if (document.getElementById("btcaddress").innerHTML == "") {
            ninja.wallets.singlewallet.generateNewAddressAndKey();
        }
        document.getElementById("singlearea").style.display = "block";
    },

    close: function () {
        document.getElementById("singlearea").style.display = "none";
    },

    // generate bitcoin address and private key and update information in the HTML
    generateNewAddressAndKey: function () {
        try {
            var key = new Bitcoin.ECKey(false);
            var bitcoinAddress = key.getBitcoinAddress();
            var privateKeyWif = key.getBitcoinWalletImportFormat();

            document.getElementById("btcaddress").innerHTML = bitcoinAddress;
            document.getElementById("btcprivwif").innerHTML = privateKeyWif;

            var keyValuePair = {
                "qrcode_public": bitcoinAddress,
                "qrcode_private": privateKeyWif
            };
            ninja.qrCode.showQrCode(keyValuePair, 4);
        }
        catch (e) {
            // browser does not have sufficient JavaScript support to generate a bitcoin address
            alert(e);
            document.getElementById("btcaddress").innerHTML = "error";
            document.getElementById("btcprivwif").innerHTML = "error";
            document.getElementById("qrcode_public").innerHTML = "";
            document.getElementById("qrcode_private").innerHTML = "";
        }
    }
};
    </script>
    <script type="text/javascript">
ninja.wallets.paperwallet = {
    open: function () {
        document.getElementById("main").setAttribute("class", "paper"); // add 'paper' class to main div
        var paperArea = document.getElementById("paperarea");
        paperArea.style.display = "block";
        var perPageLimitElement = document.getElementById("paperlimitperpage");
        var limitElement = document.getElementById("paperlimit");
        var pageBreakAt = (ninja.wallets.paperwallet.useArtisticWallet) ? ninja.wallets.paperwallet.pageBreakAtArtisticDefault : ninja.wallets.paperwallet.pageBreakAtDefault;
        if (perPageLimitElement && perPageLimitElement.value < 1) {
            perPageLimitElement.value = pageBreakAt;
        }
        if (limitElement && limitElement.value < 1) {
            limitElement.value = pageBreakAt;
        }
        if (document.getElementById("paperkeyarea").innerHTML == "") {
            document.getElementById("paperpassphrase").disabled = true;
            document.getElementById("paperencrypt").checked = false;
            ninja.wallets.paperwallet.encrypt = false;
            ninja.wallets.paperwallet.build(pageBreakAt, pageBreakAt, !document.getElementById('paperart').checked, document.getElementById('paperpassphrase').value);
        }
    },

    close: function () {
        document.getElementById("paperarea").style.display = "none";
        document.getElementById("main").setAttribute("class", ""); // remove 'paper' class from main div
    },

    remaining: null, // use to keep track of how many addresses are left to process when building the paper wallet
    count: 0,
    pageBreakAtDefault: 7,
    pageBreakAtArtisticDefault: 3,
    useArtisticWallet: true,
    pageBreakAt: null,

    build: function (numWallets, pageBreakAt, useArtisticWallet, passphrase) {
        if (numWallets < 1) numWallets = 1;
        if (pageBreakAt < 1) pageBreakAt = 1;
        ninja.wallets.paperwallet.remaining = numWallets;
        ninja.wallets.paperwallet.count = 0;
        ninja.wallets.paperwallet.useArtisticWallet = useArtisticWallet;
        ninja.wallets.paperwallet.pageBreakAt = pageBreakAt;
        document.getElementById("paperkeyarea").innerHTML = "";
        if (ninja.wallets.paperwallet.encrypt) {
            if (passphrase == "") {
                alert(ninja.translator.get("bip38alertpassphraserequired"));
                return;
            }
            document.getElementById("busyblock").className = "busy";
            ninja.privateKey.BIP38GenerateIntermediatePointAsync(passphrase, null, null, function (intermediate) {
                ninja.wallets.paperwallet.intermediatePoint = intermediate;
                document.getElementById("busyblock").className = "";
                setTimeout(ninja.wallets.paperwallet.batch, 0);
            });
        }
        else {
            setTimeout(ninja.wallets.paperwallet.batch, 0);
        }
    },

    batch: function () {
        if (ninja.wallets.paperwallet.remaining > 0) {
            var paperArea = document.getElementById("paperkeyarea");
            ninja.wallets.paperwallet.count++;
            var i = ninja.wallets.paperwallet.count;
            var pageBreakAt = ninja.wallets.paperwallet.pageBreakAt;
            var div = document.createElement("div");
            div.setAttribute("id", "keyarea" + i);
            if (ninja.wallets.paperwallet.useArtisticWallet) {
                div.innerHTML = ninja.wallets.paperwallet.templateArtisticHtml(i);
                div.setAttribute("class", "keyarea art");
            }
            else {
                div.innerHTML = ninja.wallets.paperwallet.templateHtml(i);
                div.setAttribute("class", "keyarea");
            }
            if (paperArea.innerHTML != "") {
                // page break
                if ((i - 1) % pageBreakAt == 0 && i >= pageBreakAt) {
                    var pBreak = document.createElement("div");
                    pBreak.setAttribute("class", "pagebreak");
                    document.getElementById("paperkeyarea").appendChild(pBreak);
                    div.style.pageBreakBefore = "always";
                    if (!ninja.wallets.paperwallet.useArtisticWallet) {
                        div.style.borderTop = "2px solid green";
                    }
                }
            }
            document.getElementById("paperkeyarea").appendChild(div);
            ninja.wallets.paperwallet.generateNewWallet(i);
            ninja.wallets.paperwallet.remaining--;
            setTimeout(ninja.wallets.paperwallet.batch, 0);
        }
    },

    // generate bitcoin address, private key, QR Code and update information in the HTML
    // idPostFix: 1, 2, 3, etc.
    generateNewWallet: function (idPostFix) {
        if (ninja.wallets.paperwallet.encrypt) {
            ninja.privateKey.BIP38GenerateECAddressAsync(ninja.wallets.paperwallet.intermediatePoint, false, function (address, encryptedKey) {
                if (ninja.wallets.paperwallet.useArtisticWallet) {
                    ninja.wallets.paperwallet.showArtisticWallet(idPostFix, address, encryptedKey);
                }
                else {
                    ninja.wallets.paperwallet.showWallet(idPostFix, address, encryptedKey);
                }
            });
        }
        else {
            var key = new Bitcoin.ECKey(false);
            var bitcoinAddress = key.getBitcoinAddress();
            var privateKeyWif = key.getBitcoinWalletImportFormat();
            if (ninja.wallets.paperwallet.useArtisticWallet) {
                ninja.wallets.paperwallet.showArtisticWallet(idPostFix, bitcoinAddress, privateKeyWif);
            }
            else {
                ninja.wallets.paperwallet.showWallet(idPostFix, bitcoinAddress, privateKeyWif);
            }
        }
    },

    templateHtml: function (i) {
        var privateKeyLabel = ninja.translator.get("paperlabelprivatekey");
        if (ninja.wallets.paperwallet.encrypt) {
            privateKeyLabel = ninja.translator.get("paperlabelencryptedkey");
        }

        var walletHtml =
                            "<div class='public'>" +
                                "<div id='qrcode_public" + i + "' class='qrcode_public'></div>" +
                                "<div class='pubaddress'>" +
                                    "<span class='label'>" + ninja.translator.get("paperlabelbitcoinaddress") + "</span>" +
                                    "<span class='output' id='btcaddress" + i + "'></span>" +
                                "</div>" +
                            "</div>" +
                            "<div class='private'>" +
                                "<div id='qrcode_private" + i + "' class='qrcode_private'></div>" +
                                "<div class='privwif'>" +
                                    "<span class='label'>" + privateKeyLabel + "</span>" +
                                    "<span class='output' id='btcprivwif" + i + "'></span>" +
                                "</div>" +
                            "</div>";
        return walletHtml;
    },

    showWallet: function (idPostFix, bitcoinAddress, privateKey) {
        document.getElementById("btcaddress" + idPostFix).innerHTML = bitcoinAddress;
        document.getElementById("btcprivwif" + idPostFix).innerHTML = privateKey;
        var keyValuePair = {};
        keyValuePair["qrcode_public" + idPostFix] = bitcoinAddress;
        keyValuePair["qrcode_private" + idPostFix] = privateKey;
        ninja.qrCode.showQrCode(keyValuePair);
        document.getElementById("keyarea" + idPostFix).style.display = "block";
    },
    <?php
        $image = base64_encode(file_get_contents('../paper/images/basic-ignition-coin.jpg'));
        $imageBip38 = base64_encode(file_get_contents('../paper/images/basic-ignition-coin-bip38.jpg'));
    ?>
    templateArtisticHtml: function (i) {
        var keyelement = 'btcprivwif';
        var image;
        if (ninja.wallets.paperwallet.encrypt) {
            keyelement = 'btcencryptedkey'
            image = 'data:image/jpeg;base64,<?= $imageBip38; ?>';
        }
        else {
            image = 'data:image/jpeg;base64,<?= $image; ?>';

        }

        var walletHtml =
                            "<div class='artwallet' id='artwallet" + i + "'>" +
        //"<iframe src='bitcoin-wallet-01.svg' id='papersvg" + i + "' class='papersvg' ></iframe>" +
                                "<img id='papersvg" + i + "' class='papersvg' src='" + image + "' />" +
                                "<div id='qrcode_public" + i + "' class='qrcode_public'></div>" +
                                "<div id='qrcode_private" + i + "' class='qrcode_private'></div>" +
                                "<div class='btcaddress' id='btcaddress" + i + "'></div>" +
                                "<div class='" + keyelement + "' id='" + keyelement + i + "'></div>" +
                            "</div>";
        return walletHtml;
    },

    showArtisticWallet: function (idPostFix, bitcoinAddress, privateKey) {
        var keyValuePair = {};
        keyValuePair["qrcode_public" + idPostFix] = bitcoinAddress;
        keyValuePair["qrcode_private" + idPostFix] = privateKey;
        ninja.qrCode.showQrCode(keyValuePair, 2.5);
        document.getElementById("btcaddress" + idPostFix).innerHTML = bitcoinAddress;

        if (ninja.wallets.paperwallet.encrypt) {
            var half = privateKey.length / 2;
            document.getElementById("btcencryptedkey" + idPostFix).innerHTML = privateKey.slice(0, half) + '<br />' + privateKey.slice(half);
        }
        else {
            document.getElementById("btcprivwif" + idPostFix).innerHTML = privateKey;
        }

        // CODE to modify SVG DOM elements
        //var paperSvg = document.getElementById("papersvg" + idPostFix);
        //if (paperSvg) {
        //  svgDoc = paperSvg.contentDocument;
        //  if (svgDoc) {
        //      var bitcoinAddressElement = svgDoc.getElementById("bitcoinaddress");
        //      var privateKeyElement = svgDoc.getElementById("privatekey");
        //      if (bitcoinAddressElement && privateKeyElement) {
        //          bitcoinAddressElement.textContent = bitcoinAddress;
        //          privateKeyElement.textContent = privateKeyWif;
        //      }
        //  }
        //}
    },

    toggleArt: function (element) {
        ninja.wallets.paperwallet.resetLimits();
    },

    toggleEncrypt: function (element) {
        // enable/disable passphrase textbox
        document.getElementById("paperpassphrase").disabled = !element.checked;

        if (element.checked) {
            document.getElementById("paperpassphrase").focus()
        }

        ninja.wallets.paperwallet.encrypt = element.checked;
        ninja.wallets.paperwallet.resetLimits();
    },

    resetLimits: function () {
        var hideArt = document.getElementById("paperart");
        var paperEncrypt = document.getElementById("paperencrypt");
        var limit;
        var limitperpage;

        document.getElementById("paperkeyarea").style.fontSize = "100%";
        if (!hideArt.checked) {
            limit = ninja.wallets.paperwallet.pageBreakAtArtisticDefault;
            limitperpage = ninja.wallets.paperwallet.pageBreakAtArtisticDefault;
        }
        else if (hideArt.checked && paperEncrypt.checked) {
            limit = ninja.wallets.paperwallet.pageBreakAtDefault;
            limitperpage = ninja.wallets.paperwallet.pageBreakAtDefault;
            // reduce font size
            document.getElementById("paperkeyarea").style.fontSize = "95%";
        }
        else if (hideArt.checked && !paperEncrypt.checked) {
            limit = ninja.wallets.paperwallet.pageBreakAtDefault;
            limitperpage = ninja.wallets.paperwallet.pageBreakAtDefault;
        }
        document.getElementById("paperlimitperpage").value = limitperpage;
        document.getElementById("paperlimit").value = limit;
    }
};
    </script>
    <script type="text/javascript">
ninja.wallets.bulkwallet = {
    open: function () {
        document.getElementById("bulkarea").style.display = "block";
        // show a default CSV list if the text area is empty
        if (document.getElementById("bulktextarea").value == "") {
            // return control of the thread to the browser to render the tab switch UI then build a default CSV list
            setTimeout(function () { ninja.wallets.bulkwallet.buildCSV(3, 1, document.getElementById("bulkcompressed").checked); }, 200);
        }
    },

    close: function () {
        document.getElementById("bulkarea").style.display = "none";
    },

    // use this function to bulk generate addresses
    // rowLimit: number of Bitcoin Addresses to generate
    // startIndex: add this number to the row index for output purposes
    // returns:
    // index,bitcoinAddress,privateKeyWif
    buildCSV: function (rowLimit, startIndex, compressedAddrs) {
        var bulkWallet = ninja.wallets.bulkwallet;
        document.getElementById("bulktextarea").value = ninja.translator.get("bulkgeneratingaddresses") + rowLimit;
        bulkWallet.csv = [];
        bulkWallet.csvRowLimit = rowLimit;
        bulkWallet.csvRowsRemaining = rowLimit;
        bulkWallet.csvStartIndex = --startIndex;
        bulkWallet.compressedAddrs = !!compressedAddrs;
        setTimeout(bulkWallet.batchCSV, 0);
    },

    csv: [],
    csvRowsRemaining: null, // use to keep track of how many rows are left to process when building a large CSV array
    csvRowLimit: 0,
    csvStartIndex: 0,

    batchCSV: function () {
        var bulkWallet = ninja.wallets.bulkwallet;
        if (bulkWallet.csvRowsRemaining > 0) {
            bulkWallet.csvRowsRemaining--;
            var key = new Bitcoin.ECKey(false);
            key.setCompressed(bulkWallet.compressedAddrs);

            bulkWallet.csv.push((bulkWallet.csvRowLimit - bulkWallet.csvRowsRemaining + bulkWallet.csvStartIndex)
                                + ",\"" + key.getBitcoinAddress() + "\",\"" + key.toString("wif")
            //+ "\",\"" + key.toString("wifcomp")    // uncomment these lines to add different private key formats to the CSV
            //+ "\",\"" + key.getBitcoinHexFormat()
            //+ "\",\"" + key.toString("base64")
                                + "\"");

            document.getElementById("bulktextarea").value = ninja.translator.get("bulkgeneratingaddresses") + bulkWallet.csvRowsRemaining;

            // release thread to browser to render UI
            setTimeout(bulkWallet.batchCSV, 0);
        }
        // processing is finished so put CSV in text area
        else if (bulkWallet.csvRowsRemaining === 0) {
            document.getElementById("bulktextarea").value = bulkWallet.csv.join("\n");
        }
    },

    openCloseFaq: function (faqNum) {
        // do close
        if (document.getElementById("bulka" + faqNum).style.display == "block") {
            document.getElementById("bulka" + faqNum).style.display = "none";
            document.getElementById("bulke" + faqNum).setAttribute("class", "more");
        }
        // do open
        else {
            document.getElementById("bulka" + faqNum).style.display = "block";
            document.getElementById("bulke" + faqNum).setAttribute("class", "less");
        }
    }
};
    </script>
    <script type="text/javascript">
ninja.wallets.brainwallet = {
    open: function () {
        document.getElementById("brainarea").style.display = "block";
        document.getElementById("brainpassphrase").focus();
        document.getElementById("brainwarning").innerHTML = ninja.translator.get("brainalertpassphrasewarning");
    },

    close: function () {
        document.getElementById("brainarea").style.display = "none";
    },

    minPassphraseLength: 15,

    view: function () {
        var key = document.getElementById("brainpassphrase").value.toString()
        document.getElementById("brainpassphrase").value = key;
        var keyConfirm = document.getElementById("brainpassphraseconfirm").value.toString()
        document.getElementById("brainpassphraseconfirm").value = keyConfirm;

        if (key == keyConfirm || document.getElementById("brainpassphraseshow").checked) {
            // enforce a minimum passphrase length
            if (key.length >= ninja.wallets.brainwallet.minPassphraseLength) {
                var bytes = Crypto.SHA256(key, { asBytes: true });
                var btcKey = new Bitcoin.ECKey(bytes);
                var bitcoinAddress = btcKey.getBitcoinAddress();
                var privWif = btcKey.getBitcoinWalletImportFormat();
                document.getElementById("brainbtcaddress").innerHTML = bitcoinAddress;
                document.getElementById("brainbtcprivwif").innerHTML = privWif;
                ninja.qrCode.showQrCode({
                    "brainqrcodepublic": bitcoinAddress,
                    "brainqrcodeprivate": privWif
                });
                document.getElementById("brainkeyarea").style.visibility = "visible";
            }
            else {
                alert(ninja.translator.get("brainalertpassphrasetooshort") + ninja.translator.get("brainalertpassphrasewarning"));
                ninja.wallets.brainwallet.clear();
            }
        }
        else {
            alert(ninja.translator.get("brainalertpassphrasedoesnotmatch"));
            ninja.wallets.brainwallet.clear();
        }
    },

    clear: function () {
        document.getElementById("brainkeyarea").style.visibility = "hidden";
    },

    showToggle: function (element) {
        if (element.checked) {
            document.getElementById("brainpassphrase").setAttribute("type", "text");
            document.getElementById("brainpassphraseconfirm").style.visibility = "hidden";
            document.getElementById("brainlabelconfirm").style.visibility = "hidden";
        }
        else {
            document.getElementById("brainpassphrase").setAttribute("type", "password");
            document.getElementById("brainpassphraseconfirm").style.visibility = "visible";
            document.getElementById("brainlabelconfirm").style.visibility = "visible";
        }
    }
};
    </script>
    <script type="text/javascript">
ninja.wallets.vanitywallet = {
    open: function () {
        document.getElementById("vanityarea").style.display = "block";
    },

    close: function () {
        document.getElementById("vanityarea").style.display = "none";
        document.getElementById("vanitystep1area").style.display = "none";
        document.getElementById("vanitystep2area").style.display = "none";
        document.getElementById("vanitystep1icon").setAttribute("class", "more");
        document.getElementById("vanitystep2icon").setAttribute("class", "more");
    },

    generateKeyPair: function () {
        var key = new Bitcoin.ECKey(false);
        var publicKey = key.getPubKeyHex();
        var privateKey = key.getBitcoinHexFormat();
        document.getElementById("vanitypubkey").innerHTML = publicKey;
        document.getElementById("vanityprivatekey").innerHTML = privateKey;
        document.getElementById("vanityarea").style.display = "block";
        document.getElementById("vanitystep1area").style.display = "none";
    },

    addKeys: function () {
        var privateKeyWif = ninja.translator.get("vanityinvalidinputcouldnotcombinekeys");
        var bitcoinAddress = ninja.translator.get("vanityinvalidinputcouldnotcombinekeys");
        var publicKeyHex = ninja.translator.get("vanityinvalidinputcouldnotcombinekeys");
        try {
            var input1KeyString = document.getElementById("vanityinput1").value;
            var input2KeyString = document.getElementById("vanityinput2").value;

            // both inputs are public keys
            if (ninja.publicKey.isPublicKeyHexFormat(input1KeyString) && ninja.publicKey.isPublicKeyHexFormat(input2KeyString)) {
                // add both public keys together
                if (document.getElementById("vanityradioadd").checked) {
                    var pubKeyByteArray = ninja.publicKey.getByteArrayFromAdding(input1KeyString, input2KeyString);
                    if (pubKeyByteArray == null) {
                        alert(ninja.translator.get("vanityalertinvalidinputpublickeysmatch"));
                    }
                    else {
                        privateKeyWif = ninja.translator.get("vanityprivatekeyonlyavailable");
                        bitcoinAddress = ninja.publicKey.getBitcoinAddressFromByteArray(pubKeyByteArray);
                        publicKeyHex = ninja.publicKey.getHexFromByteArray(pubKeyByteArray);
                    }
                }
                else {
                    alert(ninja.translator.get("vanityalertinvalidinputcannotmultiple"));
                }
            }
            // one public key and one private key
            else if ((ninja.publicKey.isPublicKeyHexFormat(input1KeyString) && ninja.privateKey.isPrivateKey(input2KeyString))
                            || (ninja.publicKey.isPublicKeyHexFormat(input2KeyString) && ninja.privateKey.isPrivateKey(input1KeyString))
                        ) {
                privateKeyWif = ninja.translator.get("vanityprivatekeyonlyavailable");
                var pubKeyHex = (ninja.publicKey.isPublicKeyHexFormat(input1KeyString)) ? input1KeyString : input2KeyString;
                var ecKey = (ninja.privateKey.isPrivateKey(input1KeyString)) ? new Bitcoin.ECKey(input1KeyString) : new Bitcoin.ECKey(input2KeyString);
                // add
                if (document.getElementById("vanityradioadd").checked) {
                    var pubKeyCombined = ninja.publicKey.getByteArrayFromAdding(pubKeyHex, ecKey.getPubKeyHex());
                }
                // multiply
                else {
                    var pubKeyCombined = ninja.publicKey.getByteArrayFromMultiplying(pubKeyHex, ecKey);
                }
                if (pubKeyCombined == null) {
                    alert(ninja.translator.get("vanityalertinvalidinputpublickeysmatch"));
                } else {
                    bitcoinAddress = ninja.publicKey.getBitcoinAddressFromByteArray(pubKeyCombined);
                    publicKeyHex = ninja.publicKey.getHexFromByteArray(pubKeyCombined);
                }
            }
            // both inputs are private keys
            else if (ninja.privateKey.isPrivateKey(input1KeyString) && ninja.privateKey.isPrivateKey(input2KeyString)) {
                var combinedPrivateKey;
                // add both private keys together
                if (document.getElementById("vanityradioadd").checked) {
                    combinedPrivateKey = ninja.privateKey.getECKeyFromAdding(input1KeyString, input2KeyString);
                }
                // multiply both private keys together
                else {
                    combinedPrivateKey = ninja.privateKey.getECKeyFromMultiplying(input1KeyString, input2KeyString);
                }
                if (combinedPrivateKey == null) {
                    alert(ninja.translator.get("vanityalertinvalidinputprivatekeysmatch"));
                }
                else {
                    bitcoinAddress = combinedPrivateKey.getBitcoinAddress();
                    privateKeyWif = combinedPrivateKey.getBitcoinWalletImportFormat();
                    publicKeyHex = combinedPrivateKey.getPubKeyHex();
                }
            }
        } catch (e) {
            alert(e);
        }
        document.getElementById("vanityprivatekeywif").innerHTML = privateKeyWif;
        document.getElementById("vanityaddress").innerHTML = bitcoinAddress;
        document.getElementById("vanitypublickeyhex").innerHTML = publicKeyHex;
        document.getElementById("vanitystep2area").style.display = "block";
        document.getElementById("vanitystep2icon").setAttribute("class", "less");
    },

    openCloseStep: function (num) {
        // do close
        if (document.getElementById("vanitystep" + num + "area").style.display == "block") {
            document.getElementById("vanitystep" + num + "area").style.display = "none";
            document.getElementById("vanitystep" + num + "icon").setAttribute("class", "more");
        }
        // do open
        else {
            document.getElementById("vanitystep" + num + "area").style.display = "block";
            document.getElementById("vanitystep" + num + "icon").setAttribute("class", "less");
        }
    }
};
    </script>
    <script type="text/javascript">
ninja.wallets.detailwallet = {
    open: function () {
        document.getElementById("detailarea").style.display = "block";
        document.getElementById("detailprivkey").focus();
    },

    close: function () {
        document.getElementById("detailarea").style.display = "none";
    },

    openCloseFaq: function (faqNum) {
        // do close
        if (document.getElementById("detaila" + faqNum).style.display == "block") {
            document.getElementById("detaila" + faqNum).style.display = "none";
            document.getElementById("detaile" + faqNum).setAttribute("class", "more");
        }
        // do open
        else {
            document.getElementById("detaila" + faqNum).style.display = "block";
            document.getElementById("detaile" + faqNum).setAttribute("class", "less");
        }
    },

    viewDetails: function () {
        var bip38 = false;
        var key = document.getElementById("detailprivkey").value.toString().replace(/^\s+|\s+$/g, ""); // trim white space
        document.getElementById("detailprivkey").value = key;
        var bip38CommandDisplay = document.getElementById("detailbip38commands").style.display;
        ninja.wallets.detailwallet.clear();
        if (key == "") {
            return;
        }
        if (ninja.privateKey.isBIP38Format(key)) {
            document.getElementById("detailbip38commands").style.display = bip38CommandDisplay;
            if (bip38CommandDisplay != "block") {
                document.getElementById("detailbip38commands").style.display = "block";
                document.getElementById("detailprivkeypassphrase").focus();
                return;
            }
            var passphrase = document.getElementById("detailprivkeypassphrase").value.toString()
            if (passphrase == "") {
                alert(ninja.translator.get("bip38alertpassphraserequired"));
                return;
            }
            document.getElementById("busyblock").className = "busy";
            // show Private Key BIP38 Format
            document.getElementById("detailprivbip38").innerHTML = key;
            document.getElementById("detailbip38").style.display = "block";
            ninja.privateKey.BIP38EncryptedKeyToByteArrayAsync(key, passphrase, function (btcKeyOrError) {
                document.getElementById("busyblock").className = "";
                if (btcKeyOrError.message) {
                    alert(btcKeyOrError.message);
                    ninja.wallets.detailwallet.clear();
                } else {
                    ninja.wallets.detailwallet.populateKeyDetails(new Bitcoin.ECKey(btcKeyOrError));
                }
            });
        }
        else {
            if (Bitcoin.ECKey.isMiniFormat(key)) {
                // show Private Key Mini Format
                document.getElementById("detailprivmini").innerHTML = key;
                document.getElementById("detailmini").style.display = "block";
            }
            else if (Bitcoin.ECKey.isBase6Format(key)) {
                // show Private Key Base6 Format
                document.getElementById("detailprivb6").innerHTML = key;
                document.getElementById("detailb6").style.display = "block";
            }
            var btcKey = new Bitcoin.ECKey(key);
            if (btcKey.priv == null) {
                // enforce a minimum passphrase length
                if (key.length >= ninja.wallets.brainwallet.minPassphraseLength) {
                    // Deterministic Wallet confirm box to ask if user wants to SHA256 the input to get a private key
                    var usePassphrase = confirm(ninja.translator.get("detailconfirmsha256"));
                    if (usePassphrase) {
                        var bytes = Crypto.SHA256(key, { asBytes: true });
                        var btcKey = new Bitcoin.ECKey(bytes);
                    }
                    else {
                        ninja.wallets.detailwallet.clear();
                    }
                }
                else {
                    alert(ninja.translator.get("detailalertnotvalidprivatekey"));
                    ninja.wallets.detailwallet.clear();
                }
            }
            ninja.wallets.detailwallet.populateKeyDetails(btcKey);
        }
    },

    populateKeyDetails: function (btcKey) {
        if (btcKey.priv != null) {
            btcKey.setCompressed(false);
            document.getElementById("detailprivhex").innerHTML = btcKey.toString().toUpperCase();
            document.getElementById("detailprivb64").innerHTML = btcKey.toString("base64");
            var bitcoinAddress = btcKey.getBitcoinAddress();
            var wif = btcKey.getBitcoinWalletImportFormat();
            document.getElementById("detailpubkey").innerHTML = btcKey.getPubKeyHex();
            document.getElementById("detailaddress").innerHTML = bitcoinAddress;
            document.getElementById("detailprivwif").innerHTML = wif;
            btcKey.setCompressed(true);
            var bitcoinAddressComp = btcKey.getBitcoinAddress();
            var wifComp = btcKey.getBitcoinWalletImportFormat();
            document.getElementById("detailpubkeycomp").innerHTML = btcKey.getPubKeyHex();
            document.getElementById("detailaddresscomp").innerHTML = bitcoinAddressComp;
            document.getElementById("detailprivwifcomp").innerHTML = wifComp;

            ninja.qrCode.showQrCode({
                "detailqrcodepublic": bitcoinAddress,
                "detailqrcodepubliccomp": bitcoinAddressComp,
                "detailqrcodeprivate": wif,
                "detailqrcodeprivatecomp": wifComp
            }, 4);
        }
    },

    clear: function () {
        document.getElementById("detailpubkey").innerHTML = "";
        document.getElementById("detailpubkeycomp").innerHTML = "";
        document.getElementById("detailaddress").innerHTML = "";
        document.getElementById("detailaddresscomp").innerHTML = "";
        document.getElementById("detailprivwif").innerHTML = "";
        document.getElementById("detailprivwifcomp").innerHTML = "";
        document.getElementById("detailprivhex").innerHTML = "";
        document.getElementById("detailprivb64").innerHTML = "";
        document.getElementById("detailprivb6").innerHTML = "";
        document.getElementById("detailprivmini").innerHTML = "";
        document.getElementById("detailprivbip38").innerHTML = "";
        document.getElementById("detailqrcodepublic").innerHTML = "";
        document.getElementById("detailqrcodepubliccomp").innerHTML = "";
        document.getElementById("detailqrcodeprivate").innerHTML = "";
        document.getElementById("detailqrcodeprivatecomp").innerHTML = "";
        document.getElementById("detailb6").style.display = "none";
        document.getElementById("detailmini").style.display = "none";
        document.getElementById("detailbip38commands").style.display = "none";
        document.getElementById("detailbip38").style.display = "none";
    }
};
    </script>
    <script type="text/javascript">
ninja.wallets.splitwallet = {
    open: function () {
        document.getElementById("splitarea").style.display = "block";
        secrets.setRNG();
        secrets.init(7); // 7 bits allows for up to 127 shares
    },

    close: function () {
        document.getElementById("splitarea").style.display = "none";
    },

    mkOutputRow: function (s, id, lbltxt) {
        var row = document.createElement("div");
        var label = document.createElement("label");
        label.innerHTML = lbltxt;
        var qr = document.createElement("div");
        var output = document.createElement("span");
        output.setAttribute("class", "output");
        output.innerHTML = s;

        qr.setAttribute("id", id);
        row.setAttribute("class", "splitsharerow");
        row.appendChild(label);
        row.appendChild(output);
        row.appendChild(qr);
        row.appendChild(document.createElement("br"));

        return row;
    },

    stripLeadZeros: function (hex) { return hex.split(/^0+/).slice(-1)[0]; },

    hexToBytes: function (hex) {
        //if input has odd number of digits, pad it
        if (hex.length % 2 == 1)
            hex = "0" + hex;
        for (var bytes = [], c = 0; c < hex.length; c += 2)
            bytes.push(parseInt(hex.substr(c, 2), 16));
        return bytes;
    },

    // Split a private key and update information in the HTML
    splitKey: function () {
        try {
            var numshares = parseInt(document.getElementById('splitshares').value);
            var threshold = parseInt(document.getElementById('splitthreshold').value);
            var key = new Bitcoin.ECKey(false);
            var bitcoinAddress = key.getBitcoinAddress();
            var shares = ninja.wallets.splitwallet.getFormattedShares(key.getBitcoinHexFormat(), numshares, threshold);

            var output = document.createElement("div");
            output.setAttribute("id", "splitoutput");
            var m = {};
            output.appendChild(this.mkOutputRow(bitcoinAddress, "split_addr", "Ignition Coin Address:    "));
            m["split_addr"] = bitcoinAddress;

            for (var i = 0; i < shares.length; i++) {
                var id = "split_qr_" + i;
                output.appendChild(this.mkOutputRow(shares[i], id, "Share " + (i + 1) + ":          "));
                m[id] = shares[i];
            }

            document.getElementById("splitstep1area").innerHTML = output.innerHTML;
            ninja.qrCode.showQrCode(m);

            document.getElementById("splitstep1area").style.display = "block";
            document.getElementById("splitstep1icon").setAttribute("class", "less");
        }
        catch (e) {
            // browser does not have sufficient JavaScript support to generate a bitcoin address
            alert(e);
        }
    },

    // Combine shares of a private key to retrieve the key
    combineShares: function () {
        try {
            document.getElementById("combinedprivatekey").innerHTML = "";
            var shares = document.getElementById("combineinput").value.trim().split(/\W+/);
            var combinedBytes = ninja.wallets.splitwallet.combineFormattedShares(shares);
            var privkeyBase58 = new Bitcoin.ECKey(combinedBytes).getBitcoinWalletImportFormat();
            document.getElementById("combinedprivatekey").innerHTML = privkeyBase58;
        }
        catch (e) {
            alert(e);
        }
    },

    // generate shares and format them in base58
    getFormattedShares: function (key, numshares, threshold) {
        var shares = secrets.share(key, numshares, threshold).map(ninja.wallets.splitwallet.hexToBytes).map(Bitcoin.Base58.encode);
        return shares;
    },

    // combine base58 formatted shares and return a bitcoin byte array
    combineFormattedShares: function (shares) {
        var combined = secrets.combine(shares.map(Bitcoin.Base58.decode).map(Crypto.util.bytesToHex).map(ninja.wallets.splitwallet.stripLeadZeros));
        return ninja.wallets.splitwallet.hexToBytes(combined);
    },

    openCloseStep: function (num) {
        // do close
        if (document.getElementById("splitstep" + num + "area").style.display == "block") {
            document.getElementById("splitstep" + num + "area").style.display = "none";
            document.getElementById("splitstep" + num + "icon").setAttribute("class", "more");
        }
        // do open
        else {
            document.getElementById("splitstep" + num + "area").style.display = "block";
            document.getElementById("splitstep" + num + "icon").setAttribute("class", "less");
        }
    }
};

    </script>
    <script type="text/javascript">
(function (ninja) {
    var ut = ninja.unitTests = {
        runSynchronousTests: function () {
            document.getElementById("busyblock").className = "busy";
            var div = document.createElement("div");
            div.setAttribute("class", "unittests");
            div.setAttribute("id", "unittests");
            var testResults = "";
            var passCount = 0;
            var testCount = 0;
            for (var test in ut.synchronousTests) {
                var exceptionMsg = "";
                var resultBool = false;
                try {
                    resultBool = ut.synchronousTests[test]();
                } catch (ex) {
                    exceptionMsg = ex.toString();
                    resultBool = false;
                }
                if (resultBool == true) {
                    var passFailStr = "pass";
                    passCount++;
                }
                else {
                    var passFailStr = "<b>FAIL " + exceptionMsg + "</b>";
                }
                testCount++;
                testResults += test + ": " + passFailStr + "<br/>";
            }
            testResults += passCount + " of " + testCount + " synchronous tests passed";
            if (passCount < testCount) {
                testResults += "<b>" + (testCount - passCount) + " unit test(s) failed</b>";
            }
            div.innerHTML = "<h3>Unit Tests</h3><div id=\"unittestresults\">" + testResults + "<br/><br/></div>";
            document.body.appendChild(div);
            document.getElementById("busyblock").className = "";

        },

        runAsynchronousTests: function () {
            var div = document.createElement("div");
            div.setAttribute("class", "unittests");
            div.setAttribute("id", "asyncunittests");
            div.innerHTML = "<h3>Async Unit Tests</h3><div id=\"asyncunittestresults\"></div><br/><br/><br/><br/>";
            document.body.appendChild(div);

            // run the asynchronous tests one after another so we don't crash the browser
            ninja.foreachSerialized(ninja.unitTests.asynchronousTests, function (name, cb) {
                document.getElementById("busyblock").className = "busy";
                ninja.unitTests.asynchronousTests[name](cb);
            }, function () {
                document.getElementById("asyncunittestresults").innerHTML += "running of asynchronous unit tests complete!<br/>";
                document.getElementById("busyblock").className = "";
            });
        },

        synchronousTests: {
            //ninja.publicKey tests
            testIsPublicKeyHexFormat: function () {
                var key = "0478982F40FA0C0B7A55717583AFC99A4EDFD301A2729DC59B0B8EB9E18692BCB521F054FAD982AF4CC1933AFD1F1B563EA779A6AA6CCE36A30B947DD653E63E44";
                var bool = ninja.publicKey.isPublicKeyHexFormat(key);
                if (bool != true) {
                    return false;
                }
                return true;
            },
            testGetHexFromByteArray: function () {
                var bytes = [4, 120, 152, 47, 64, 250, 12, 11, 122, 85, 113, 117, 131, 175, 201, 154, 78, 223, 211, 1, 162, 114, 157, 197, 155, 11, 142, 185, 225, 134, 146, 188, 181, 33, 240, 84, 250, 217, 130, 175, 76, 193, 147, 58, 253, 31, 27, 86, 62, 167, 121, 166, 170, 108, 206, 54, 163, 11, 148, 125, 214, 83, 230, 62, 68];
                var key = ninja.publicKey.getHexFromByteArray(bytes);
                if (key != "0478982F40FA0C0B7A55717583AFC99A4EDFD301A2729DC59B0B8EB9E18692BCB521F054FAD982AF4CC1933AFD1F1B563EA779A6AA6CCE36A30B947DD653E63E44") {
                    return false;
                }
                return true;
            },
            testHexToBytes: function () {
                var key = "0478982F40FA0C0B7A55717583AFC99A4EDFD301A2729DC59B0B8EB9E18692BCB521F054FAD982AF4CC1933AFD1F1B563EA779A6AA6CCE36A30B947DD653E63E44";
                var bytes = Crypto.util.hexToBytes(key);
                if (bytes.toString() != "4,120,152,47,64,250,12,11,122,85,113,117,131,175,201,154,78,223,211,1,162,114,157,197,155,11,142,185,225,134,146,188,181,33,240,84,250,217,130,175,76,193,147,58,253,31,27,86,62,167,121,166,170,108,206,54,163,11,148,125,214,83,230,62,68") {
                    return false;
                }
                return true;
            },
            testGetBitcoinAddressFromByteArray: function () {
                var bytes = [4, 120, 152, 47, 64, 250, 12, 11, 122, 85, 113, 117, 131, 175, 201, 154, 78, 223, 211, 1, 162, 114, 157, 197, 155, 11, 142, 185, 225, 134, 146, 188, 181, 33, 240, 84, 250, 217, 130, 175, 76, 193, 147, 58, 253, 31, 27, 86, 62, 167, 121, 166, 170, 108, 206, 54, 163, 11, 148, 125, 214, 83, 230, 62, 68];
                var address = ninja.publicKey.getBitcoinAddressFromByteArray(bytes);
                if (address != "1Cnz9ULjzBPYhDw1J8bpczDWCEXnC9HuU1") {
                    return false;
                }
                return true;
            },
            testGetByteArrayFromAdding: function () {
                var key1 = "0478982F40FA0C0B7A55717583AFC99A4EDFD301A2729DC59B0B8EB9E18692BCB521F054FAD982AF4CC1933AFD1F1B563EA779A6AA6CCE36A30B947DD653E63E44";
                var key2 = "0419153E53FECAD7FF07FEC26F7DDEB1EDD66957711AA4554B8475F10AFBBCD81C0159DC0099AD54F733812892EB9A11A8C816A201B3BAF0D97117EBA2033C9AB2";
                var bytes = ninja.publicKey.getByteArrayFromAdding(key1, key2);
                if (bytes.toString() != "4,151,19,227,152,54,37,184,255,4,83,115,216,102,189,76,82,170,57,4,196,253,2,41,74,6,226,33,167,199,250,74,235,223,128,233,99,150,147,92,57,39,208,84,196,71,68,248,166,106,138,95,172,253,224,70,187,65,62,92,81,38,253,79,0") {
                    return false;
                }
                return true;
            },
            testGetByteArrayFromAddingCompressed: function () {
                var key1 = "0278982F40FA0C0B7A55717583AFC99A4EDFD301A2729DC59B0B8EB9E18692BCB5";
                var key2 = "0219153E53FECAD7FF07FEC26F7DDEB1EDD66957711AA4554B8475F10AFBBCD81C";
                var bytes = ninja.publicKey.getByteArrayFromAdding(key1, key2);
                var hex = ninja.publicKey.getHexFromByteArray(bytes);
                if (hex != "029713E3983625B8FF045373D866BD4C52AA3904C4FD02294A06E221A7C7FA4AEB") {
                    return false;
                }
                return true;
            },
            testGetByteArrayFromAddingUncompressedAndCompressed: function () {
                var key1 = "0478982F40FA0C0B7A55717583AFC99A4EDFD301A2729DC59B0B8EB9E18692BCB521F054FAD982AF4CC1933AFD1F1B563EA779A6AA6CCE36A30B947DD653E63E44";
                var key2 = "0219153E53FECAD7FF07FEC26F7DDEB1EDD66957711AA4554B8475F10AFBBCD81C";
                var bytes = ninja.publicKey.getByteArrayFromAdding(key1, key2);
                if (bytes.toString() != "4,151,19,227,152,54,37,184,255,4,83,115,216,102,189,76,82,170,57,4,196,253,2,41,74,6,226,33,167,199,250,74,235,223,128,233,99,150,147,92,57,39,208,84,196,71,68,248,166,106,138,95,172,253,224,70,187,65,62,92,81,38,253,79,0") {
                    return false;
                }
                return true;
            },
            testGetByteArrayFromAddingShouldReturnNullWhenSameKey1: function () {
                var key1 = "0478982F40FA0C0B7A55717583AFC99A4EDFD301A2729DC59B0B8EB9E18692BCB521F054FAD982AF4CC1933AFD1F1B563EA779A6AA6CCE36A30B947DD653E63E44";
                var key2 = "0478982F40FA0C0B7A55717583AFC99A4EDFD301A2729DC59B0B8EB9E18692BCB521F054FAD982AF4CC1933AFD1F1B563EA779A6AA6CCE36A30B947DD653E63E44";
                var bytes = ninja.publicKey.getByteArrayFromAdding(key1, key2);
                if (bytes != null) {
                    return false;
                }
                return true;
            },
            testGetByteArrayFromAddingShouldReturnNullWhenSameKey2: function () {
                var key1 = "0478982F40FA0C0B7A55717583AFC99A4EDFD301A2729DC59B0B8EB9E18692BCB521F054FAD982AF4CC1933AFD1F1B563EA779A6AA6CCE36A30B947DD653E63E44";
                var key2 = "0278982F40FA0C0B7A55717583AFC99A4EDFD301A2729DC59B0B8EB9E18692BCB5";
                var bytes = ninja.publicKey.getByteArrayFromAdding(key1, key2);
                if (bytes != null) {
                    return false;
                }
                return true;
            },
            testGetByteArrayFromMultiplying: function () {
                var key1 = "0478982F40FA0C0B7A55717583AFC99A4EDFD301A2729DC59B0B8EB9E18692BCB521F054FAD982AF4CC1933AFD1F1B563EA779A6AA6CCE36A30B947DD653E63E44";
                var key2 = "SQE6yipP5oW8RBaStWoB47xsRQ8pat";
                var bytes = ninja.publicKey.getByteArrayFromMultiplying(key1, new Bitcoin.ECKey(key2));
                if (bytes.toString() != "4,102,230,163,180,107,9,21,17,48,35,245,227,110,199,119,144,57,41,112,64,245,182,40,224,41,230,41,5,26,206,138,57,115,35,54,105,7,180,5,106,217,57,229,127,174,145,215,79,121,163,191,211,143,215,50,48,156,211,178,72,226,68,150,52") {
                    return false;
                }
                return true;
            },
            testGetByteArrayFromMultiplyingCompressedOutputsUncompressed: function () {
                var key1 = "0278982F40FA0C0B7A55717583AFC99A4EDFD301A2729DC59B0B8EB9E18692BCB5";
                var key2 = "SQE6yipP5oW8RBaStWoB47xsRQ8pat";
                var bytes = ninja.publicKey.getByteArrayFromMultiplying(key1, new Bitcoin.ECKey(key2));
                if (bytes.toString() != "4,102,230,163,180,107,9,21,17,48,35,245,227,110,199,119,144,57,41,112,64,245,182,40,224,41,230,41,5,26,206,138,57,115,35,54,105,7,180,5,106,217,57,229,127,174,145,215,79,121,163,191,211,143,215,50,48,156,211,178,72,226,68,150,52") {
                    return false;
                }
                return true;
            },
            testGetByteArrayFromMultiplyingCompressedOutputsCompressed: function () {
                var key1 = "0278982F40FA0C0B7A55717583AFC99A4EDFD301A2729DC59B0B8EB9E18692BCB5";
                var key2 = "L1n4cgNZAo2KwdUc15zzstvo1dcxpBw26NkrLqfDZtU9AEbPkLWu";
                var ecKey = new Bitcoin.ECKey(key2);
                var bytes = ninja.publicKey.getByteArrayFromMultiplying(key1, ecKey);
                if (bytes.toString() != "2,102,230,163,180,107,9,21,17,48,35,245,227,110,199,119,144,57,41,112,64,245,182,40,224,41,230,41,5,26,206,138,57") {
                    return false;
                }
                return true;
            },
            testGetByteArrayFromMultiplyingShouldReturnNullWhenSameKey1: function () {
                var key1 = "0478982F40FA0C0B7A55717583AFC99A4EDFD301A2729DC59B0B8EB9E18692BCB521F054FAD982AF4CC1933AFD1F1B563EA779A6AA6CCE36A30B947DD653E63E44";
                var key2 = "5J8QhiQtAiozKwyk3GCycAscg1tNaYhNdiiLey8vaDK8Bzm4znb";
                var bytes = ninja.publicKey.getByteArrayFromMultiplying(key1, new Bitcoin.ECKey(key2));
                if (bytes != null) {
                    return false;
                }
                return true;
            },
            testGetByteArrayFromMultiplyingShouldReturnNullWhenSameKey2: function () {
                var key1 = "0278982F40FA0C0B7A55717583AFC99A4EDFD301A2729DC59B0B8EB9E18692BCB5";
                var key2 = "KxbhchnQquYQ2dfSxz7rrEaQTCukF4uCV57TkamyTbLzjFWcdi3S";
                var bytes = ninja.publicKey.getByteArrayFromMultiplying(key1, new Bitcoin.ECKey(key2));
                if (bytes != null) {
                    return false;
                }
                return true;
            },
            // confirms multiplication is working and BigInteger was created correctly (Pub Key B vs Priv Key A)
            testGetPubHexFromMultiplyingPrivAPubB: function () {
                var keyPub = "04F04BF260DCCC46061B5868F60FE962C77B5379698658C98A93C3129F5F98938020F36EBBDE6F1BEAF98E5BD0E425747E68B0F2FB7A2A59EDE93F43C0D78156FF";
                var keyPriv = "B1202A137E917536B3B4C5010C3FF5DDD4784917B3EEF21D3A3BF21B2E03310C";
                var bytes = ninja.publicKey.getByteArrayFromMultiplying(keyPub, new Bitcoin.ECKey(keyPriv));
                var pubHex = ninja.publicKey.getHexFromByteArray(bytes);
                if (pubHex != "04C6732006AF4AE571C7758DF7A7FB9E3689DFCF8B53D8724D3A15517D8AB1B4DBBE0CB8BB1C4525F8A3001771FC7E801D3C5986A555E2E9441F1AD6D181356076") {
                    return false;
                }
                return true;
            },
            // confirms multiplication is working and BigInteger was created correctly (Pub Key A vs Priv Key B)
            testGetPubHexFromMultiplyingPrivBPubA: function () {
                var keyPub = "0429BF26C0AF7D31D608474CEBD49DA6E7C541B8FAD95404B897643476CE621CFD05E24F7AE8DE8033AADE5857DB837E0B704A31FDDFE574F6ECA879643A0D3709";
                var keyPriv = "7DE52819F1553C2BFEDE6A2628B6FDDF03C2A07EB21CF77ACA6C2C3D252E1FD9";
                var bytes = ninja.publicKey.getByteArrayFromMultiplying(keyPub, new Bitcoin.ECKey(keyPriv));
                var pubHex = ninja.publicKey.getHexFromByteArray(bytes);
                if (pubHex != "04C6732006AF4AE571C7758DF7A7FB9E3689DFCF8B53D8724D3A15517D8AB1B4DBBE0CB8BB1C4525F8A3001771FC7E801D3C5986A555E2E9441F1AD6D181356076") {
                    return false;
                }
                return true;
            },

            // Private Key tests
            testBadKeyIsNotWif: function () {
                return !(Bitcoin.ECKey.isWalletImportFormat("bad key"));
            },
            testBadKeyIsNotWifCompressed: function () {
                return !(Bitcoin.ECKey.isCompressedWalletImportFormat("bad key"));
            },
            testBadKeyIsNotHex: function () {
                return !(Bitcoin.ECKey.isHexFormat("bad key"));
            },
            testBadKeyIsNotBase64: function () {
                return !(Bitcoin.ECKey.isBase64Format("bad key"));
            },
            testBadKeyIsNotMini: function () {
                return !(Bitcoin.ECKey.isMiniFormat("bad key"));
            },
            testBadKeyReturnsNullPrivFromECKey: function () {
                var key = "bad key";
                var ecKey = new Bitcoin.ECKey(key);
                if (ecKey.priv != null) {
                    return false;
                }
                return true;
            },
            testGetBitcoinPrivateKeyByteArray: function () {
                var key = "5J8QhiQtAiozKwyk3GCycAscg1tNaYhNdiiLey8vaDK8Bzm4znb";
                var bytes = [41, 38, 101, 195, 135, 36, 24, 173, 241, 218, 127, 250, 58, 100, 111, 47, 6, 2, 36, 109, 166, 9, 138, 145, 210, 41, 195, 33, 80, 242, 113, 139];
                var btcKey = new Bitcoin.ECKey(key);
                if (btcKey.getBitcoinPrivateKeyByteArray().toString() != bytes.toString()) {
                    return false;
                }
                return true;
            },
            testECKeyDecodeWalletImportFormat: function () {
                var key = "5J8QhiQtAiozKwyk3GCycAscg1tNaYhNdiiLey8vaDK8Bzm4znb";
                var bytes1 = [41, 38, 101, 195, 135, 36, 24, 173, 241, 218, 127, 250, 58, 100, 111, 47, 6, 2, 36, 109, 166, 9, 138, 145, 210, 41, 195, 33, 80, 242, 113, 139];
                var bytes2 = Bitcoin.ECKey.decodeWalletImportFormat(key);
                if (bytes1.toString() != bytes2.toString()) {
                    return false;
                }
                return true;
            },
            testECKeyDecodeCompressedWalletImportFormat: function () {
                var key = "KxbhchnQquYQ2dfSxz7rrEaQTCukF4uCV57TkamyTbLzjFWcdi3S";
                var bytes1 = [41, 38, 101, 195, 135, 36, 24, 173, 241, 218, 127, 250, 58, 100, 111, 47, 6, 2, 36, 109, 166, 9, 138, 145, 210, 41, 195, 33, 80, 242, 113, 139];
                var bytes2 = Bitcoin.ECKey.decodeCompressedWalletImportFormat(key);
                if (bytes1.toString() != bytes2.toString()) {
                    return false;
                }
                return true;
            },
            testWifToPubKeyHex: function () {
                var key = "5J8QhiQtAiozKwyk3GCycAscg1tNaYhNdiiLey8vaDK8Bzm4znb";
                var btcKey = new Bitcoin.ECKey(key);
                if (btcKey.getPubKeyHex() != "0478982F40FA0C0B7A55717583AFC99A4EDFD301A2729DC59B0B8EB9E18692BCB521F054FAD982AF4CC1933AFD1F1B563EA779A6AA6CCE36A30B947DD653E63E44"
                        || btcKey.getPubPoint().compressed != false) {
                    return false;
                }
                return true;
            },
            testWifToPubKeyHexCompressed: function () {
                var key = "5J8QhiQtAiozKwyk3GCycAscg1tNaYhNdiiLey8vaDK8Bzm4znb";
                var btcKey = new Bitcoin.ECKey(key);
                btcKey.setCompressed(true);
                if (btcKey.getPubKeyHex() != "0278982F40FA0C0B7A55717583AFC99A4EDFD301A2729DC59B0B8EB9E18692BCB5"
                        || btcKey.getPubPoint().compressed != true) {
                    return false;
                }
                return true;
            },
            testBase64ToECKey: function () {
                var key = "KSZlw4ckGK3x2n/6OmRvLwYCJG2mCYqR0inDIVDycYs=";
                var btcKey = new Bitcoin.ECKey(key);
                if (btcKey.getBitcoinBase64Format() != "KSZlw4ckGK3x2n/6OmRvLwYCJG2mCYqR0inDIVDycYs=") {
                    return false;
                }
                return true;
            },
            testHexToECKey: function () {
                var key = "292665C3872418ADF1DA7FFA3A646F2F0602246DA6098A91D229C32150F2718B";
                var btcKey = new Bitcoin.ECKey(key);
                if (btcKey.getBitcoinHexFormat() != "292665C3872418ADF1DA7FFA3A646F2F0602246DA6098A91D229C32150F2718B") {
                    return false;
                }
                return true;
            },
            testCompressedWifToECKey: function () {
                var key = "KxbhchnQquYQ2dfSxz7rrEaQTCukF4uCV57TkamyTbLzjFWcdi3S";
                var btcKey = new Bitcoin.ECKey(key);
                if (btcKey.getBitcoinWalletImportFormat() != "KxbhchnQquYQ2dfSxz7rrEaQTCukF4uCV57TkamyTbLzjFWcdi3S"
                        || btcKey.getPubPoint().compressed != true) {
                    return false;
                }
                return true;
            },
            testWifToECKey: function () {
                var key = "5J8QhiQtAiozKwyk3GCycAscg1tNaYhNdiiLey8vaDK8Bzm4znb";
                var btcKey = new Bitcoin.ECKey(key);
                if (btcKey.getBitcoinWalletImportFormat() != "5J8QhiQtAiozKwyk3GCycAscg1tNaYhNdiiLey8vaDK8Bzm4znb") {
                    return false;
                }
                return true;
            },
            testBrainToECKey: function () {
                var key = "bitaddress.org unit test";
                var bytes = Crypto.SHA256(key, { asBytes: true });
                var btcKey = new Bitcoin.ECKey(bytes);
                if (btcKey.getBitcoinWalletImportFormat() != "5J8QhiQtAiozKwyk3GCycAscg1tNaYhNdiiLey8vaDK8Bzm4znb") {
                    return false;
                }
                return true;
            },
            testMini30CharsToECKey: function () {
                var key = "SQE6yipP5oW8RBaStWoB47xsRQ8pat";
                var btcKey = new Bitcoin.ECKey(key);
                if (btcKey.getBitcoinWalletImportFormat() != "5JrBLQseeZdYw4jWEAHmNxGMr5fxh9NJU3fUwnv4khfKcg2rJVh") {
                    return false;
                }
                return true;
            },
            testGetECKeyFromAdding: function () {
                var key1 = "5J8QhiQtAiozKwyk3GCycAscg1tNaYhNdiiLey8vaDK8Bzm4znb";
                var key2 = "SQE6yipP5oW8RBaStWoB47xsRQ8pat";
                var ecKey = ninja.privateKey.getECKeyFromAdding(key1, key2);
                if (ecKey.getBitcoinWalletImportFormat() != "5KAJTSqSjpsZ11KyEE3qu5PrJVjR4ZCbNxK3Nb1F637oe41m1c2") {
                    return false;
                }
                return true;
            },
            testGetECKeyFromAddingCompressed: function () {
                var key1 = "KxbhchnQquYQ2dfSxz7rrEaQTCukF4uCV57TkamyTbLzjFWcdi3S";
                var key2 = "L1n4cgNZAo2KwdUc15zzstvo1dcxpBw26NkrLqfDZtU9AEbPkLWu";
                var ecKey = ninja.privateKey.getECKeyFromAdding(key1, key2);
                if (ecKey.getBitcoinWalletImportFormat() != "L3A43j2pc2J8F2SjBNbYprPrcDpDCh8Aju8dUH65BEM2r7RFSLv4") {
                    return false;
                }
                return true;
            },
            testGetECKeyFromAddingUncompressedAndCompressed: function () {
                var key1 = "5J8QhiQtAiozKwyk3GCycAscg1tNaYhNdiiLey8vaDK8Bzm4znb";
                var key2 = "L1n4cgNZAo2KwdUc15zzstvo1dcxpBw26NkrLqfDZtU9AEbPkLWu";
                var ecKey = ninja.privateKey.getECKeyFromAdding(key1, key2);
                if (ecKey.getBitcoinWalletImportFormat() != "5KAJTSqSjpsZ11KyEE3qu5PrJVjR4ZCbNxK3Nb1F637oe41m1c2") {
                    return false;
                }
                return true;
            },
            testGetECKeyFromAddingShouldReturnNullWhenSameKey1: function () {
                var key1 = "5J8QhiQtAiozKwyk3GCycAscg1tNaYhNdiiLey8vaDK8Bzm4znb";
                var key2 = "5J8QhiQtAiozKwyk3GCycAscg1tNaYhNdiiLey8vaDK8Bzm4znb";
                var ecKey = ninja.privateKey.getECKeyFromAdding(key1, key2);
                if (ecKey != null) {
                    return false;
                }
                return true;
            },
            testGetECKeyFromAddingShouldReturnNullWhenSameKey2: function () {
                var key1 = "5J8QhiQtAiozKwyk3GCycAscg1tNaYhNdiiLey8vaDK8Bzm4znb";
                var key2 = "KxbhchnQquYQ2dfSxz7rrEaQTCukF4uCV57TkamyTbLzjFWcdi3S";
                var ecKey = ninja.privateKey.getECKeyFromAdding(key1, key2);
                if (ecKey != null) {
                    return false;
                }
                return true;
            },
            testGetECKeyFromMultiplying: function () {
                var key1 = "5J8QhiQtAiozKwyk3GCycAscg1tNaYhNdiiLey8vaDK8Bzm4znb";
                var key2 = "SQE6yipP5oW8RBaStWoB47xsRQ8pat";
                var ecKey = ninja.privateKey.getECKeyFromMultiplying(key1, key2);
                if (ecKey.getBitcoinWalletImportFormat() != "5KetpZ5mCGagCeJnMmvo18n4iVrtPSqrpnW5RP92Gv2BQy7GPCk") {
                    return false;
                }
                return true;
            },
            testGetECKeyFromMultiplyingCompressed: function () {
                var key1 = "KxbhchnQquYQ2dfSxz7rrEaQTCukF4uCV57TkamyTbLzjFWcdi3S";
                var key2 = "L1n4cgNZAo2KwdUc15zzstvo1dcxpBw26NkrLqfDZtU9AEbPkLWu";
                var ecKey = ninja.privateKey.getECKeyFromMultiplying(key1, key2);
                if (ecKey.getBitcoinWalletImportFormat() != "L5LFitc24jme2PfVChJS3bKuQAPBp54euuqLWciQdF2CxnaU3M8t") {
                    return false;
                }
                return true;
            },
            testGetECKeyFromMultiplyingUncompressedAndCompressed: function () {
                var key1 = "5J8QhiQtAiozKwyk3GCycAscg1tNaYhNdiiLey8vaDK8Bzm4znb";
                var key2 = "L1n4cgNZAo2KwdUc15zzstvo1dcxpBw26NkrLqfDZtU9AEbPkLWu";
                var ecKey = ninja.privateKey.getECKeyFromMultiplying(key1, key2);
                if (ecKey.getBitcoinWalletImportFormat() != "5KetpZ5mCGagCeJnMmvo18n4iVrtPSqrpnW5RP92Gv2BQy7GPCk") {
                    return false;
                }
                return true;
            },
            testGetECKeyFromMultiplyingShouldReturnNullWhenSameKey1: function () {
                var key1 = "5J8QhiQtAiozKwyk3GCycAscg1tNaYhNdiiLey8vaDK8Bzm4znb";
                var key2 = "5J8QhiQtAiozKwyk3GCycAscg1tNaYhNdiiLey8vaDK8Bzm4znb";
                var ecKey = ninja.privateKey.getECKeyFromMultiplying(key1, key2);
                if (ecKey != null) {
                    return false;
                }
                return true;
            },
            testGetECKeyFromMultiplyingShouldReturnNullWhenSameKey2: function () {
                var key1 = "5J8QhiQtAiozKwyk3GCycAscg1tNaYhNdiiLey8vaDK8Bzm4znb";
                var key2 = "KxbhchnQquYQ2dfSxz7rrEaQTCukF4uCV57TkamyTbLzjFWcdi3S";
                var ecKey = ninja.privateKey.getECKeyFromMultiplying(key1, key2);
                if (ecKey != null) {
                    return false;
                }
                return true;
            },
            testGetECKeyFromBase6Key: function () {
                var baseKey = "100531114202410255230521444145414341221420541210522412225005202300434134213212540304311321323051431";
                var hexKey = "292665C3872418ADF1DA7FFA3A646F2F0602246DA6098A91D229C32150F2718B";
                var ecKey = new Bitcoin.ECKey(baseKey);
                if (ecKey.getBitcoinHexFormat() != hexKey) {
                    return false;
                }
                return true;
            },

            // EllipticCurve tests
            testDecodePointEqualsDecodeFrom: function () {
                var key = "04F04BF260DCCC46061B5868F60FE962C77B5379698658C98A93C3129F5F98938020F36EBBDE6F1BEAF98E5BD0E425747E68B0F2FB7A2A59EDE93F43C0D78156FF";
                var ecparams = EllipticCurve.getSECCurveByName("secp256k1");
                var ecPoint1 = EllipticCurve.PointFp.decodeFrom(ecparams.getCurve(), Crypto.util.hexToBytes(key));
                var ecPoint2 = ecparams.getCurve().decodePointHex(key);
                if (!ecPoint1.equals(ecPoint2)) {
                    return false;
                }
                return true;
            },
            testDecodePointHexForCompressedPublicKey: function () {
                var key = "03F04BF260DCCC46061B5868F60FE962C77B5379698658C98A93C3129F5F989380";
                var pubHexUncompressed = ninja.publicKey.getDecompressedPubKeyHex(key);
                if (pubHexUncompressed != "04F04BF260DCCC46061B5868F60FE962C77B5379698658C98A93C3129F5F98938020F36EBBDE6F1BEAF98E5BD0E425747E68B0F2FB7A2A59EDE93F43C0D78156FF") {
                    return false;
                }
                return true;
            },
            // old bugs
            testBugWithLeadingZeroBytePublicKey: function () {
                var key = "5Je7CkWTzgdo1RpwjYhwnVKxQXt8EPRq17WZFtWcq5umQdsDtTP";
                var btcKey = new Bitcoin.ECKey(key);
                if (btcKey.getBitcoinAddress() != "1M6dsMZUjFxjdwsyVk8nJytWcfr9tfUa9E") {
                    return false;
                }
                return true;
            },
            testBugWithLeadingZeroBytePrivateKey: function () {
                var key = "0004d30da67214fa65a41a6493576944c7ea86713b14db437446c7a8df8e13da";
                var btcKey = new Bitcoin.ECKey(key);
                if (btcKey.getBitcoinAddress() != "1NAjZjF81YGfiJ3rTKc7jf1nmZ26KN7Gkn") {
                    return false;
                }
                return true;
            },

            // test split wallet
            testSplitAndCombinePrivateKey2of2: function () {
                // lowercase hex key
                var key = "0004d30da67214fa65a41a6493576944c7ea86713b14db437446c7a8df8e13da"; //5HpJ4bpHFEMWYwCidjtZHwM2rsMh4PRfmZKV8Y21i7msiUkQKUW
                var numshares = 2;
                var threshold = 2;
                secrets.setRNG();
                secrets.init(7);

                var shares = ninja.wallets.splitwallet.getFormattedShares(key, numshares, threshold);
                var combined = ninja.wallets.splitwallet.combineFormattedShares(shares);
                var btcKey = new Bitcoin.ECKey(combined);

                if (btcKey.getBitcoinHexFormat() != key.toUpperCase()) {
                    return false;
                }
                return true;
            },
            // Example use case #1:
            // Division of 3 shares:
            //   1 share in a safety deposit box ("Box")
            //   1 share at Home
            //   1 share at Work
            // Threshold of 2 can be redeemed in these permutations
            //   Home + Box
            //   Work + Box
            //   Home + Work
            testSplitAndCombinePrivateKey2of3: function () {
                // lowercase hex key
                var key = "0004d30da67214fa65a41a6493576944c7ea86713b14db437446c7a8df8e13da"; //5HpJ4bpHFEMWYwCidjtZHwM2rsMh4PRfmZKV8Y21i7msiUkQKUW
                var numshares = 3;
                var threshold = 2;
                secrets.setRNG();
                secrets.init(7);

                var shares = ninja.wallets.splitwallet.getFormattedShares(key, numshares, threshold);
                shares.shift();
                var combined = ninja.wallets.splitwallet.combineFormattedShares(shares);
                var btcKey = new Bitcoin.ECKey(combined);

                if (btcKey.getBitcoinHexFormat() != key.toUpperCase()) {
                    return false;
                }
                return true;
            },
            testSplitAndCombinePrivateKey2of4: function () {
                // uppercase hex key
                var key = "292665C3872418ADF1DA7FFA3A646F2F0602246DA6098A91D229C32150F2718B"; //5J8QhiQtAiozKwyk3GCycAscg1tNaYhNdiiLey8vaDK8Bzm4znb
                var numshares = 4;
                var threshold = 2;
                secrets.setRNG();
                secrets.init(7);

                var shares = ninja.wallets.splitwallet.getFormattedShares(key, numshares, threshold);
                shares.shift();
                shares.shift();
                var combined = ninja.wallets.splitwallet.combineFormattedShares(shares);
                var btcKey = new Bitcoin.ECKey(combined);

                if (btcKey.getBitcoinHexFormat() != key) {
                    return false;
                }
                return true;
            },
            // Example use case #2:
            // Division of 13 shares:
            //   4 shares in a safety deposit box ("Box")
            //   3 shares with good friend Angie
            //   3 shares with good friend Fred
            //   3 shares with Self at home or office
            // Threshold of 7 can be redeemed in these permutations
            //   Self + Box (no trust to spend your coins but your friends are backing up your shares)
            //   Angie + Box (Angie will send btc to executor of your will)
            //   Fred + Box (if Angie hasn't already then Fred will send btc to executor of your will)
            //   Angie + Fred + Self (bank fire/theft then you with both your friends can spend the coins)
            testSplitAndCombinePrivateKey7of13: function () {
                var key = "0004d30da67214fa65a41a6493576944c7ea86713b14db437446c7a8df8e13da";
                var numshares = 12;
                var threshold = 7;
                secrets.setRNG();
                secrets.init(7);

                var shares = ninja.wallets.splitwallet.getFormattedShares(key, numshares, threshold);
                var combined = ninja.wallets.splitwallet.combineFormattedShares(shares);
                var btcKey = new Bitcoin.ECKey(combined);

                if (btcKey.getBitcoinHexFormat() != key.toUpperCase()) {
                    return false;
                }
                return true;
            },
            testCombinePrivateKeyFromXofYShares: function () {
                var key = "5K9nHKqbwc1xXpa6wV5p3AaCnubvxQDBukKaFkq7ThAkxgMTMEh";
                // these are 4 of 6 shares
                var shares = ["3XxjMASmrkk6eXMM9kAJA7qiqViNVBfiwA1GQDLvg4PVScL", "3Y2DkcPuNX8VKZwpnDdxw55wJtcnCvv2nALqe8nBLViHvck",
                    "3Y6qv7kyGwgRBKVHVbUNtzmLYAZWQtTPztPwR8wc7uf4MXR", "3YD4TowZn6jw5ss8U89vrcPHonFW4vSs9VKq8MupV5kevG4"]
                secrets.setRNG();
                secrets.init(7);

                var combined = ninja.wallets.splitwallet.combineFormattedShares(shares);
                var btcKey = new Bitcoin.ECKey(combined);
                if (btcKey.getBitcoinWalletImportFormat() != key) {
                    return false;
                }
                return true;
            }
        },

        asynchronousTests: {
            //https://en.bitcoin.it/wiki/BIP_0038
            testBip38: function (done) {
                var tests = [
                //No compression, no EC multiply
                    ["6PRVWUbkzzsbcVac2qwfssoUJAN1Xhrg6bNk8J7Nzm5H7kxEbn2Nh2ZoGg", "TestingOneTwoThree", "5KN7MzqK5wt2TP1fQCYyHBtDrXdJuXbUzm4A9rKAteGu3Qi5CVR"],
                    ["6PRNFFkZc2NZ6dJqFfhRoFNMR9Lnyj7dYGrzdgXXVMXcxoKTePPX1dWByq", "Satoshi", "5HtasZ6ofTHP6HCwTqTkLDuLQisYPah7aUnSKfC7h4hMUVw2gi5"],
                //Compression, no EC multiply
                    ["6PYNKZ1EAgYgmQfmNVamxyXVWHzK5s6DGhwP4J5o44cvXdoY7sRzhtpUeo", "TestingOneTwoThree", "L44B5gGEpqEDRS9vVPz7QT35jcBG2r3CZwSwQ4fCewXAhAhqGVpP"],
                    ["6PYLtMnXvfG3oJde97zRyLYFZCYizPU5T3LwgdYJz1fRhh16bU7u6PPmY7", "Satoshi", "KwYgW8gcxj1JWJXhPSu4Fqwzfhp5Yfi42mdYmMa4XqK7NJxXUSK7"],
                //EC multiply, no compression, no lot/sequence numbers
                    ["6PfQu77ygVyJLZjfvMLyhLMQbYnu5uguoJJ4kMCLqWwPEdfpwANVS76gTX", "TestingOneTwoThree", "5K4caxezwjGCGfnoPTZ8tMcJBLB7Jvyjv4xxeacadhq8nLisLR2"],
                    ["6PfLGnQs6VZnrNpmVKfjotbnQuaJK4KZoPFrAjx1JMJUa1Ft8gnf5WxfKd", "Satoshi", "5KJ51SgxWaAYR13zd9ReMhJpwrcX47xTJh2D3fGPG9CM8vkv5sH"],
                //EC multiply, no compression, lot/sequence numbers
                    ["6PgNBNNzDkKdhkT6uJntUXwwzQV8Rr2tZcbkDcuC9DZRsS6AtHts4Ypo1j", "MOLON LABE", "5JLdxTtcTHcfYcmJsNVy1v2PMDx432JPoYcBTVVRHpPaxUrdtf8"],
                    ["6PgGWtx25kUg8QWvwuJAgorN6k9FbE25rv5dMRwu5SKMnfpfVe5mar2ngH", Crypto.charenc.UTF8.bytesToString([206, 156, 206, 159, 206, 155, 206, 169, 206, 157, 32, 206, 155, 206, 145, 206, 146, 206, 149])/*UTF-8 characters, encoded in source so they don't get corrupted*/, "5KMKKuUmAkiNbA3DazMQiLfDq47qs8MAEThm4yL8R2PhV1ov33D"]];

                // running each test uses a lot of memory, which isn't freed
                // immediately, so give the VM a little time to reclaim memory
                function waitThenCall(callback) {
                    return function () { setTimeout(callback, 10000); }
                }

                var decryptTest = function (test, i, onComplete) {
                    ninja.privateKey.BIP38EncryptedKeyToByteArrayAsync(test[0], test[1], function (privBytes) {
                        if (privBytes.constructor == Error) {
                            document.getElementById("asyncunittestresults").innerHTML += "fail testDecryptBip38 #" + i + ", error: " + privBytes.message + "<br/>";
                        } else {
                            var btcKey = new Bitcoin.ECKey(privBytes);
                            var wif = !test[2].substr(0, 1).match(/[LK]/) ? btcKey.setCompressed(false).getBitcoinWalletImportFormat() : btcKey.setCompressed(true).getBitcoinWalletImportFormat();
                            if (wif != test[2]) {
                                document.getElementById("asyncunittestresults").innerHTML += "fail testDecryptBip38 #" + i + "<br/>";
                            } else {
                                document.getElementById("asyncunittestresults").innerHTML += "pass testDecryptBip38 #" + i + "<br/>";
                            }
                        }
                        onComplete();
                    });
                };

                var encryptTest = function (test, compressed, i, onComplete) {
                    ninja.privateKey.BIP38PrivateKeyToEncryptedKeyAsync(test[2], test[1], compressed, function (encryptedKey) {
                        if (encryptedKey === test[0]) {
                            document.getElementById("asyncunittestresults").innerHTML += "pass testBip38Encrypt #" + i + "<br/>";
                        } else {
                            document.getElementById("asyncunittestresults").innerHTML += "fail testBip38Encrypt #" + i + "<br/>";
                            document.getElementById("asyncunittestresults").innerHTML += "expected " + test[0] + "<br/>received " + encryptedKey + "<br/>";
                        }
                        onComplete();
                    });
                };

                // test randomly generated encryption-decryption cycle
                var cycleTest = function (i, compress, onComplete) {
                    // create new private key
                    var privKey = (new Bitcoin.ECKey(false)).getBitcoinWalletImportFormat();

                    // encrypt private key
                    ninja.privateKey.BIP38PrivateKeyToEncryptedKeyAsync(privKey, 'testing', compress, function (encryptedKey) {
                        // decrypt encryptedKey
                        ninja.privateKey.BIP38EncryptedKeyToByteArrayAsync(encryptedKey, 'testing', function (decryptedBytes) {
                            var decryptedKey = (new Bitcoin.ECKey(decryptedBytes)).getBitcoinWalletImportFormat();

                            if (decryptedKey === privKey) {
                                document.getElementById("asyncunittestresults").innerHTML += "pass cycleBip38 test #" + i + "<br/>";
                            }
                            else {
                                document.getElementById("asyncunittestresults").innerHTML += "fail cycleBip38 test #" + i + " " + privKey + "<br/>";
                                document.getElementById("asyncunittestresults").innerHTML += "encrypted key: " + encryptedKey + "<br/>decrypted key: " + decryptedKey;
                            }
                            onComplete();
                        });
                    });
                };

                // intermediate test - create some encrypted keys from an intermediate
                // then decrypt them to check that the private keys are recoverable
                var intermediateTest = function (i, onComplete) {
                    var pass = Math.random().toString(36).substr(2);
                    ninja.privateKey.BIP38GenerateIntermediatePointAsync(pass, null, null, function (intermediatePoint) {
                        ninja.privateKey.BIP38GenerateECAddressAsync(intermediatePoint, false, function (address, encryptedKey) {
                            ninja.privateKey.BIP38EncryptedKeyToByteArrayAsync(encryptedKey, pass, function (privBytes) {
                                if (privBytes.constructor == Error) {
                                    document.getElementById("asyncunittestresults").innerHTML += "fail testBip38Intermediate #" + i + ", error: " + privBytes.message + "<br/>";
                                } else {
                                    var btcKey = new Bitcoin.ECKey(privBytes);
                                    var btcAddress = btcKey.getBitcoinAddress();
                                    if (address !== btcKey.getBitcoinAddress()) {
                                        document.getElementById("asyncunittestresults").innerHTML += "fail testBip38Intermediate #" + i + "<br/>";
                                    } else {
                                        document.getElementById("asyncunittestresults").innerHTML += "pass testBip38Intermediate #" + i + "<br/>";
                                    }
                                }
                                onComplete();
                            });
                        });
                    });
                }

                document.getElementById("asyncunittestresults").innerHTML += "running " + tests.length + " tests named testDecryptBip38<br/>";
                document.getElementById("asyncunittestresults").innerHTML += "running 4 tests named testBip38Encrypt<br/>";
                document.getElementById("asyncunittestresults").innerHTML += "running 2 tests named cycleBip38<br/>";
                document.getElementById("asyncunittestresults").innerHTML += "running 5 tests named testBip38Intermediate<br/>";
                ninja.runSerialized([
                    function (cb) {
                        ninja.forSerialized(0, tests.length, function (i, callback) {
                            decryptTest(tests[i], i, waitThenCall(callback));
                        }, waitThenCall(cb));
                    },
                    function (cb) {
                        ninja.forSerialized(0, 4, function (i, callback) {
                            // only first 4 test vectors are not EC-multiply,
                            // compression param false for i = 1,2 and true for i = 3,4
                            encryptTest(tests[i], i >= 2, i, waitThenCall(callback));
                        }, waitThenCall(cb));
                    },
                    function (cb) {
                        ninja.forSerialized(0, 2, function (i, callback) {
                            cycleTest(i, i % 2 ? true : false, waitThenCall(callback));
                        }, waitThenCall(cb));
                    },
                    function (cb) {
                        ninja.forSerialized(0, 5, function (i, callback) {
                            intermediateTest(i, waitThenCall(callback));
                        }, cb);
                    }
                ], done);
            }
        }
    };
})(ninja);
    </script>
    <script type="text/javascript">
// run unit tests
if (ninja.getQueryString()["unittests"] == "true" || ninja.getQueryString()["unittests"] == "1") {
    ninja.unitTests.runSynchronousTests();
    ninja.translator.showEnglishJson();
}
// run async unit tests
if (ninja.getQueryString()["asyncunittests"] == "true" || ninja.getQueryString()["asyncunittests"] == "1") {
    ninja.unitTests.runAsynchronousTests();
}
// change language
if (ninja.getQueryString()["culture"] != undefined) {
    ninja.translator.translate(ninja.getQueryString()["culture"]);
} else {
    ninja.translator.autoDetectTranslation();
}
// testnet, check if testnet edition should be activated
if (ninja.getQueryString()["testnet"] == "true" || ninja.getQueryString()["testnet"] == "1") {
    document.getElementById("testnet").innerHTML = ninja.translator.get("testneteditionactivated");
    document.getElementById("testnet").style.display = "block";
    document.getElementById("detailwifprefix").innerHTML = "'9'";
    document.getElementById("detailcompwifprefix").innerHTML = "'c'";
    Bitcoin.Address.networkVersion = 0x6F; // testnet
    Bitcoin.ECKey.privateKeyPrefix = 0xEF; // testnet
    ninja.testnetMode = true;
}
if (ninja.getQueryString()["showseedpool"] == "true" || ninja.getQueryString()["showseedpool"] == "1") {
    document.getElementById("seedpoolarea").style.display = "block";
}
    </script>
