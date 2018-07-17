/********************************************************
           Brand new JS functions added for bitcoinpaperwallet.com
           ********************************************************/

// Dynamic drawing of standard bitcoin design
if (typeof PaperWallet == "undefined") {
    // global PaperWallet object
    var PaperWallet = window.PaperWallet = {};
}
if (!PaperWallet.draw) {
    (function() {
        // images minimized with https://tinypng.com/ and http://pnggauntlet.com/ and encoded with http://www.base64-image.de/
        var imgBitcoinLogo = new Image();
        imgBitcoinLogo.src = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAATEAAABlCAMAAAAS0yQPAAAAsVBMVEUAAAD////////////////////////////////////////////////////////////////////////////////////////////////////3kxr7kwD90ar3jQ38tWv/+fT3khf6nCf7pUH+5dH92rf3kBL/8+r+7dz7rlf4oDX+4MT9xI38vXz//vz4lyH9zJz806L5q03+69T+6M36tWH+8uP6u278zZT82Kv7xIL3igbY4iM2AAAAGXRSTlMAeUWrl+673YhmVg82H8sI1MMWKfblb6Jei/6cWwAADftJREFUeNrs2N2OojAYBuCKIKAMgjrq9yWMlcAiG0HwB3Tv/8LWtG5kHcqgiSfW57ik5E37QktaG1j9qdF1Hf1Td9yuMZ1bI/ImMp6r9ifc0pyZaQ3J2y2rY09AyFGVAXm7+pja8BNdtcgbp8w0aMWdv3fnmdKDqkOQnb5y349jP9+fymMEVY4pfWZWF662mzzGW0u/CBaVzPpEZiPj2va/9ykK5ZXQehL3WV+Hi0O2xB+UO7iYdCTdmkMVLrYrbMMP4MIdEwmNXeCiFbYVB8BpErZZXwMm2eA98i1wBpHMFLhgiXfaANeVq8wM4Aq8nx8B48p0RleBiXx8RJoBY8sT2SWwXYoPWksWmQFMiWJhSLHJHhhXjguNKTBrFKOxj54XothqweufSEBp0flhkGyzIsbGyGT5yRhrTSuMo+kBzn4tKSIVrbUVMHPy4oZuY4dxYZ7A2ZYihqdyH1OPCrtMe/Vzucq/ktjIW/NYPaQYASx2ZVoXWcFGOa/9J8tLLErxO1pJ7MiG7T0MY7baDrWJYdayyoZKv6P29JlJRAZKx7TI0zw+zYBf7/hYx/NCWq2xRRyi98UeOHpYix8yG99B6fQ0+MeuHTpQHX6L1FPIczw+jSH8TNLl7lj4PLXQT9jWpYhexp8QJMYH2kNxXjr8xybfmXDV+yBP8Pg04wmcBfVlDwBJxFL7U1xrjK2ixA+xXslGmuISqKgfOvpLq7kupw0DURgwlBAKadPSdLFs4Qu2sQmXhCRN3v/B2q6Q67VuHs/0+5HMRMYrr3ePjkQ8Mv79f5RZ/zAePn6kbckKEMxa2ZKxnMgYAQ9mVybrP4U2D+qcKNP/sI3oHeYz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";

        var draw = PaperWallet.draw = {
            imgBitcoinLogo: imgBitcoinLogo,

            /**
             * Draw guilloche pattern with the given parameters into the canvas.
             * Source: https://gist.github.com/3n/803329
             */
            guilloche: function(ctx, opts) {
                ctx.save();

                var opts = opts || {};
                var scale = opts.scale || 1,
                    majorR = opts.majorR || 49,
                    minorR = opts.minorR || 20.4324,
                    angleMultiplier = opts.angleMultiplier || 1,
                    radiusEffectConstant = opts.radiusEffectConstant || 33,
                    steps = opts.steps || 1000,
                    centerPoint = opts.centerPoint || {
                        x: 0,
                        y: 0
                    },
                    color = opts.color || 'rgb(0,0,255)',
                    width = opts.width || 0.5;
                globalAlpha = opts.globalAlpha || 1.0;

                ctx.globalAlpha = globalAlpha;

                var diff = majorR - minorR,
                    s = diff / minorR,
                    theta = 0,
                    radiusEffect = radiusEffectConstant + minorR,
                    oldX, oldY;

                for (var i = steps; i > 0; --i) {
                    var new_theta = angleMultiplier * theta,
                        x = (diff * Math.sin(new_theta) + radiusEffect * Math.sin(new_theta * s)) * scale + (centerPoint.x),
                        y = (diff * Math.cos(new_theta) - radiusEffect * Math.cos(new_theta * s)) * scale + (centerPoint.y);

                    theta += Math.PI * 4 / steps;

                    if (oldX) {
                        ctx.strokeStyle = color;
                        ctx.lineWidth = width;
                        ctx.beginPath();
                        ctx.moveTo(oldX, oldY);
                        ctx.lineTo(x, y);
                        ctx.closePath();
                        ctx.stroke();
                    }

                    oldX = x;
                    oldY = y;
                }

                ctx.restore();
            },

            drawIgnition : function(ctx) {

               var alpha = ctx.globalAlpha;
      var gradient;

          // example/
          ctx.save();
          ctx.restore();

          // layer1/Group
          ctx.save();

          // layer1/Group/Path
          ctx.save();
          ctx.beginPath();
          ctx.moveTo(1015.5, 891.9);
          ctx.lineTo(55.5, 891.9);
          ctx.lineTo(55.5, 79.1);
          ctx.lineTo(1015.5, 79.1);
          ctx.fillStyle = "rgb(254, 234, 58)";
          ctx.fill();

          // layer1/Group/Path
          // This artwork uses an unsupported "Multiply" blending mode
          ctx.globalAlpha = alpha * 0.30;
          ctx.beginPath();
          ctx.moveTo(1015.5, 891.9);
          ctx.lineTo(55.5, 891.9);
          ctx.lineTo(55.5, 79.1);
          ctx.lineTo(1015.5, 79.1);
          gradient = ctx.createLinearGradient(535.5, 79.1, 535.5, 891.9);
          gradient.addColorStop(0.00, "rgb(255, 255, 255)");
          gradient.addColorStop(1.00, "rgb(0, 0, 0)");
          ctx.fillStyle = gradient;
          ctx.fill();

          // layer1/Group/Path
          ctx.globalAlpha = alpha * 1.00;
          ctx.beginPath();
          ctx.moveTo(2815.1, 88.0);
          ctx.lineTo(2306.4, 182.3);
          ctx.lineTo(1706.3, 182.3);
          ctx.lineTo(1706.3, 79.1);
          ctx.lineTo(1015.5, 79.1);
          ctx.lineTo(1015.5, 891.9);
          ctx.lineTo(1706.3, 891.9);
          ctx.lineTo(1706.3, 788.7);
          ctx.lineTo(2306.4, 788.7);
          ctx.lineTo(2813.5, 882.7);
          ctx.lineTo(2906.5, 788.7);
          ctx.lineTo(2906.5, 182.3);
          ctx.lineTo(2815.1, 88.0);
          ctx.closePath();
          gradient = ctx.createLinearGradient(1032.0, 485.5, 2016.0, 485.5);
          gradient.addColorStop(0.00, "rgb(0, 93, 127)");
          gradient.addColorStop(1.00, "rgb(151, 172, 199)");
          ctx.fillStyle = gradient;
          ctx.fill();

          // layer1/Group
          ctx.restore();

          // layer1/Group/Path
          ctx.save();
          ctx.globalAlpha = alpha * 0.40;
          ctx.beginPath();
          ctx.moveTo(735.0, 507.7);
          ctx.lineTo(756.3, 538.5);
          ctx.lineTo(724.0, 659.5);
          ctx.lineTo(735.0, 507.7);
          ctx.closePath();
          ctx.fillStyle = "rgb(89, 89, 89)";
          ctx.fill();

          // layer1/Group/Path
          ctx.beginPath();
          ctx.moveTo(820.8, 311.4);
          ctx.lineTo(810.7, 464.1);
          ctx.lineTo(788.5, 432.4);
          ctx.lineTo(820.8, 311.4);
          ctx.closePath();
          ctx.fill();

          // layer1/Group/Path
          ctx.globalAlpha = alpha * 1.00;
          ctx.beginPath();
          ctx.moveTo(653.8, 448.8);
          ctx.bezierCurveTo(636.1, 515.4, 675.6, 583.9, 742.2, 601.6);
          ctx.bezierCurveTo(808.8, 619.4, 877.2, 579.9, 895.0, 513.3);
          ctx.bezierCurveTo(912.8, 446.7, 873.3, 378.3, 806.7, 360.5);
          ctx.bezierCurveTo(740.1, 342.7, 671.6, 382.2, 653.8, 448.8);
          ctx.closePath();
          ctx.fillStyle = "rgb(38, 34, 97)";
          ctx.fill();

          // layer1/Group/Path
          ctx.beginPath();
          ctx.moveTo(819.4, 310.0);
          ctx.lineTo(711.2, 464.8);
          ctx.lineTo(787.1, 431.0);
          ctx.lineTo(819.4, 310.0);
          ctx.closePath();
          ctx.fillStyle = "rgb(254, 234, 58)";
          ctx.fill();

          // layer1/Group/Path
          ctx.beginPath();
          ctx.moveTo(710.1, 468.8);
          ctx.lineTo(759.1, 536.0);
          ctx.lineTo(786.0, 435.0);
          ctx.lineTo(710.1, 468.8);
          ctx.closePath();
          ctx.fill();

          // layer1/Group/Path
          ctx.globalAlpha = alpha * 0.80;
          ctx.beginPath();
          ctx.moveTo(786.0, 435.0);
          ctx.lineTo(759.1, 536.0);
          ctx.lineTo(835.0, 502.2);
          ctx.lineTo(786.0, 435.0);
          ctx.closePath();
          ctx.fillStyle = "rgb(253, 216, 55)";
          ctx.fill();

          // layer1/Group/Path
          ctx.beginPath();
          ctx.moveTo(833.9, 506.2);
          ctx.lineTo(758.0, 540.0);
          ctx.lineTo(725.7, 661.0);
          ctx.lineTo(833.9, 506.2);
          ctx.closePath();
          ctx.fill();

          // layer1/Rectangle
          ctx.restore();
          ctx.beginPath();
          ctx.moveTo(2773.1, 678.6);
          ctx.lineTo(2434.7, 678.6);
          ctx.bezierCurveTo(2421.5, 678.6, 2410.8, 667.9, 2410.8, 654.7);
          ctx.lineTo(2410.8, 316.3);
          ctx.bezierCurveTo(2410.8, 303.1, 2421.5, 292.4, 2434.7, 292.4);
          ctx.lineTo(2773.1, 292.4);
          ctx.bezierCurveTo(2786.3, 292.4, 2797.0, 303.1, 2797.0, 316.3);
          ctx.lineTo(2797.0, 654.7);
          ctx.bezierCurveTo(2797.0, 667.9, 2786.3, 678.6, 2773.1, 678.6);
          ctx.closePath();
          ctx.fillStyle = "rgb(255, 255, 255)";
          ctx.fill();

          // layer1/Rectangle
          ctx.beginPath();
          ctx.moveTo(2175.6, 678.6);
          ctx.lineTo(1837.2, 678.6);
          ctx.bezierCurveTo(1824.0, 678.6, 1813.3, 667.9, 1813.3, 654.7);
          ctx.lineTo(1813.3, 316.3);
          ctx.bezierCurveTo(1813.3, 303.1, 1824.0, 292.4, 1837.2, 292.4);
          ctx.lineTo(2175.6, 292.4);
          ctx.bezierCurveTo(2188.8, 292.4, 2199.5, 303.1, 2199.5, 316.3);
          ctx.lineTo(2199.5, 654.7);
          ctx.bezierCurveTo(2199.5, 667.9, 2188.8, 678.6, 2175.6, 678.6);
          ctx.closePath();
          ctx.fill();

          // layer1/Rectangle
          ctx.beginPath();
          ctx.moveTo(395.4, 638.1);
          ctx.lineTo(138.0, 638.1);
          ctx.bezierCurveTo(124.8, 638.1, 114.1, 627.4, 114.1, 614.2);
          ctx.lineTo(114.1, 356.8);
          ctx.bezierCurveTo(114.1, 343.6, 124.8, 332.9, 138.0, 332.9);
          ctx.lineTo(395.4, 332.9);
          ctx.bezierCurveTo(408.6, 332.9, 419.3, 343.6, 419.3, 356.8);
          ctx.lineTo(419.3, 614.2);
          ctx.bezierCurveTo(419.3, 627.4, 408.6, 638.1, 395.4, 638.1);
          ctx.closePath();
          ctx.fill();

          // layer1/Guide

          // layer1/Guide

          // layer1/Path
          ctx.beginPath();
          ctx.moveTo(1048.5, 79.1);
          ctx.lineTo(989.1, 79.1);
          ctx.lineTo(989.1, 891.9);
          ctx.lineTo(1048.5, 891.9);
          ctx.lineTo(1092.7, 485.5);
          ctx.lineTo(1048.5, 79.1);
          ctx.closePath();
          ctx.fillStyle = "rgb(0, 0, 0)";
          ctx.fill();

          // layer1/Path
          ctx.beginPath();
          ctx.moveTo(1045.2, 79.1);
          ctx.lineTo(985.8, 79.1);
          ctx.lineTo(985.8, 891.9);
          ctx.lineTo(1045.2, 891.9);
          ctx.lineTo(1089.4, 485.5);
          ctx.lineTo(1045.2, 79.1);
          ctx.closePath();
          ctx.fillStyle = "rgb(38, 34, 97)";
          ctx.fill();

          // layer1/Rectangle
          ctx.beginPath();
          ctx.moveTo(2962.0, 971.0);
          ctx.lineTo(0.0, 971.0);
          ctx.lineTo(0.0, 0.0);
          ctx.lineTo(2962.0, 0.0);
          ctx.lineTo(2962.0, 971.0);
          ctx.closePath();
          ctx.restore();
                },

            /**
             * Calculate unique parameters for pattern drawing.
             * @param {String} msg Message to extract parameters from.
             */
            guillocheParams: function(msg) {
                /**
                 * Extracts a float in range [0, 1[ from first 4 bytes of the given bytearray.
                 * This treats the first 4 bytes as a signed integer value, scales it to size 1,
                 * and adds 0.5 to move to the range [0, 1[.
                 *
                 * @param {bytearray} Byte array, at least 4 bytes
                 */
                function float01(bytearray) {
                    var v = bytearray[0] << 24;
                    v |= bytearray[1] << 16;
                    v |= bytearray[2] << 8;
                    v |= bytearray[3];
                    v /= 4294967296;

                    // since v is signed, just add 0.5 to remap to [0, 1[.
                    return v + 0.5;
                }

                /**
                 * Linear interpolation between min and max based on v [0, 1[.
                 */
                function scaled_between(v, min, max) {
                    return v * (max - min) + min;
                };

                // this contains some hand tuned constant to make the generated patterns look pretty.
                var msg = Crypto.SHA256(msg, {
                    asBytes: true
                });
                var majorR = scaled_between(float01(msg), 55, 300); // overall radius.

                var msg = Crypto.SHA256(msg, {
                    asBytes: true
                });
                var scale = scaled_between(float01(msg), 5, 15);

                msg = Crypto.SHA256(msg, {
                    asBytes: true
                });
                var minorR = scaled_between(float01(msg), 0.01, 10); // a large minorR makes for a sparser design, bigger holes in the spiderweb.

                msg = Crypto.SHA256(msg, {
                    asBytes: true
                });
                var steps = scaled_between(float01(msg), 1500, 7000);

                var width = (5000 / steps) * .5;
                width = Math.min(width, 1.2); // prevent overly chunky lines

                msg = Crypto.SHA256(msg, {
                    asBytes: true
                });
                var radiusEffectConstant = scaled_between(float01(msg), majorR * 0.9, majorR);

                msg = Crypto.SHA256(msg, {
                    asBytes: true
                });
                var angleMultiplier = Math.ceil(scaled_between(float01(msg), 0, 3));

                // majorR = 110; scale = 11; minorR = .5; steps = 5000; width = .3; radiusEffectConstant = 100; angleMultiplier = 2; // test suite

                return {
                    angleMultiplier: angleMultiplier,
                    majorR: majorR,
                    minorR: minorR,
                    radiusEffectConstant: radiusEffectConstant,
                    scale: scale,
                    steps: steps,
                    width: width
                }
            },

            /**
             * Draws a rounded rectangle using the current state of the canvas.
             * If you omit the last three params, it will draw a rectangle
             * outline with a 5 pixel border radius
             *
             * Source: http://stackoverflow.com/a/3368118
             *
             * @param {CanvasRenderingContext2D} ctx
             * @param {Number} x The top left x coordinate
             * @param {Number} y The top left y coordinate
             * @param {Number} width The width of the rectangle
             * @param {Number} height The height of the rectangle
             * @param {Number} radius The corner radius. Defaults to 5;
             * @param {Boolean} fill Whether to fill the rectangle. Defaults to false.
             * @param {Boolean} stroke Whether to stroke the rectangle. Defaults to true.
             */
            roundRect: function(ctx, x, y, w, h, opts) {
                ctx.save();

                ctx.beginPath();
                ctx.moveTo(x + opts.radius, y);
                ctx.lineTo(x + w - opts.radius, y);
                ctx.quadraticCurveTo(x + w, y, x + w, y + opts.radius);
                ctx.lineTo(x + w, y + h - opts.radius);
                ctx.quadraticCurveTo(x + w, y + h, x + w - opts.radius, y + h);
                ctx.lineTo(x + opts.radius, y + h);
                ctx.quadraticCurveTo(x, y + h, x, y + h - opts.radius);
                ctx.lineTo(x, y + opts.radius);
                ctx.quadraticCurveTo(x, y, x + opts.radius, y);
                ctx.closePath();

                if (typeof opts.lineWidth != "undefined") {
                    ctx.lineWidth = opts.lineWidth;
                }
                if (typeof opts.strokeStyle != "undefined") {
                    ctx.strokeStyle = opts.strokeStyle;
                    ctx.stroke();
                }
                if (typeof opts.fillStyle != "undefined") {
                    ctx.fillStyle = opts.fillStyle;
                    ctx.fill();
                }

                ctx.restore();
            },

            /**
             * Draw text with strong blur around it.
             * This is quite a hack, but I couldn't find a better way.
             */
            fillTextStrongBlur: function(ctx, msg, x, y, offset, opts) {
                ctx.save();

                ctx.shadowColor = opts.color.textShadow;
                ctx.shadowBlur = 7;
                ctx.shadowOffsetX = 4;
                ctx.shadowOffsetY = 4;

                // run multiple times for stronger blur
                for (var i = 0; i < 1; ++i) {
                    ctx.shadowOffsetX = offset;
                    ctx.shadowOffsetY = offset;
                    ctx.fillText(msg, x, y);

                    ctx.shadowOffsetX = -offset;
                    ctx.shadowOffsetY = offset;
                    ctx.fillText(msg, x, y);

                    ctx.shadowOffsetX = offset;
                    ctx.shadowOffsetY = -offset;
                    ctx.fillText(msg, x, y);

                    ctx.shadowOffsetX = -offset;
                    ctx.shadowOffsetY = -offset;
                    ctx.fillText(msg, x, y);
                }

                ctx.restore();
            },

            text: function(ctx, opts) {
                ctx.save();

                ctx.font = opts.font;
                ctx.textAlign = "center";
                ctx.fillStyle = opts.color.text;
                draw.fillTextStrongBlur(ctx, opts.text.publicAddress, 268, 320, 4, opts);
                draw.fillTextStrongBlur(ctx, opts.text.depositVerify, 268, 696, 4, opts);

                ctx.rotate(-Math.PI * 2 / 4);
                draw.fillTextStrongBlur(ctx, opts.text.privateKey, -482, 2396, 4, opts);
                draw.fillTextStrongBlur(ctx, opts.text.walletImportFormat, -482, 2844, 4, opts);

                // text for green pointer
                ctx.fillStyle = opts.color.textPointer;
                ctx.fillText(opts.text.withdraw, -482, 1015);

                ctx.restore();
            },

            obfuscation: function(ctx, x, y, w, h) {
                ctx.save();
                ctx.font = "18px Courier";
                ctx.textAlign = "center";
                var obfuscationColors = ["#000000", "#222222", "#333333", "#666666"];

                // create random text to black out private key pattern
                var possible_letters = "฿#";
                // var possible_letters = "■•";
                for (var i = 0; i < 1500; ++i) {
                    ctx.fillStyle = obfuscationColors[Math.floor(Math.random() * obfuscationColors.length)];
                    var letter = possible_letters[Math.floor(Math.random() * possible_letters.length)];
                    ctx.fillText(letter, x + Math.random() * w, y + Math.random() * h);
                }
                ctx.restore();
            },

            gradients: function(ctx, opts) {
                ctx.save();

                // draw background gradients
                var grd = ctx.createLinearGradient(55, 82, 55, 807);
                grd.addColorStop(0, opts.color.publicUpper);
                grd.addColorStop(1, opts.color.publicLower);
                ctx.fillStyle = grd;
                ctx.fillRect(55, 82, 958, 807);

                grd = ctx.createLinearGradient(1013, 0, 2961, 0)
                grd.addColorStop(0, opts.color.privateLeft);
                grd.addColorStop(1, opts.color.privateRight);
                ctx.fillStyle = grd;
                ctx.fillRect(1013, 82, 2961, 807);
                ctx.restore();
            },

            logos: function(ctx, cp) {
                var w = 90;

                /* draw circle for bitcoin logo
                ctx.arc(cp.x, cp.y, w/2+5, 0, 2*Math.PI, false);
                ctx.fillStyle = "white";
                ctx.fill();
                */

                // logo
                ctx.drawImage(PaperWallet.draw.imgBitcoinLogo, (cp.x - w / 2) - 4, (cp.y - w / 2) - 4, 305, 101);
            },

            pointer: function(ctx, colorPointer) {
                ctx.save();
                ctx.beginPath();
                ctx.moveTo(980, 82);
                ctx.lineTo(1030, 82);
                ctx.lineTo(1030, 355);
                ctx.quadraticCurveTo(1030, 466, 1070, 486);
                ctx.quadraticCurveTo(1030, 506, 1030, 617);
                ctx.lineTo(1030, 889);
                ctx.lineTo(980, 889);
                ctx.closePath();
                ctx.fillStyle = colorPointer;
                ctx.shadowColor = "#555";
                ctx.shadowBlur = 5;
                ctx.shadowOffsetX = 5;
                ctx.shadowOffsetY = 0;
                ctx.fill();
                ctx.restore();
            },

            clearSurroundings: function(ctx) {
                ctx.save();

                ctx.beginPath();
                ctx.moveTo(55, 82);
                ctx.lineTo(1708, 82);
                ctx.lineTo(1708, 182);
                ctx.lineTo(2308, 182);
                ctx.lineTo(2809, 90);
                ctx.lineTo(2906, 182);

                ctx.lineTo(2906, 971 - 182);
                ctx.lineTo(2809, 971 - 90);
                ctx.lineTo(2308, 971 - 182);
                ctx.lineTo(1708, 971 - 182);
                ctx.lineTo(1708, 971 - 82);
                ctx.lineTo(55, 971 - 82);

                // surroundings,  move back
                ctx.lineTo(0, 971);
                ctx.lineTo(2962, 971);
                ctx.lineTo(2962, 0);
                ctx.lineTo(0, 0);
                ctx.lineTo(0, 971);
                ctx.lineTo(55, 971);
                ctx.closePath();

                ctx.lineWidth = 0.2;
                ctx.strokeStyle = "black";
                ctx.fillStyle = "white";

                ctx.stroke();
                ctx.fill();

                ctx.restore();
            },

            // Creates an image based on the given public key.
            frontImage: function(key, opts) {
                console.log(opts)
                // create the temporary draw canvas
                var canvas = document.createElement('canvas');
                canvas.width = 2962;
                canvas.height = 971;

                var key = key || "";

                var ctx = canvas.getContext('2d');
                var cp = {
                    x: 665,
                    y: 389
                };


                PaperWallet.draw.gradients(ctx, opts);

                // draw guilloche
                guillocheParams = PaperWallet.draw.guillocheParams(key);
                guillocheParams.color = opts.color.guilloche;
                guillocheParams.centerPoint = cp;
                PaperWallet.draw.guilloche(ctx, guillocheParams);
                PaperWallet.draw.logos(ctx, cp);
                PaperWallet.draw.pointer(ctx, opts.color.pointer);

                PaperWallet.draw.clearSurroundings(ctx);


                // draw empty rectangle for public QR code
                var qrOpts = {
                    lineWidth: 0.2,
                    strokeStyle: "black",
                    fillStyle: "white",
                    radius: 20,
                };



                PaperWallet.draw.roundRect(ctx, 113, 344, 310, 310, qrOpts);
                PaperWallet.draw.roundRect(ctx, 1816, 290, 384, 384, qrOpts);
                PaperWallet.draw.roundRect(ctx, 2416, 290, 384, 384, qrOpts);

                PaperWallet.draw.text(ctx, opts);

                PaperWallet.draw.obfuscation(ctx, 1831, 310, 350, 350);
                PaperWallet.draw.drawIgnition(ctx)

                return canvas.toDataURL();
            }
        };
    })();
}

// global drawing options, most of which will be replaced in setDesign() for translation

var drawOpts = {
    color: {
        publicUpper: "#fff57c",
        publicLower: "#f7931a",
        privateLeft: "#8cd96f",
        privateRight: "#fff67d",
        pointer: "#03ab5d",
        guilloche: "white",

        text: "#1937a9",
        textShadow: "white",
        textPointer: "white",
    },

    font: "bold 20pt sans-serif",

    text: {
        publicAddress: "",
        depositVerify: "",
        privateKey: "",
        walletImportFormat: "",
        withdraw: "",
    },
};

// Functions for printer calibration

var inlineMediaStyle = null;

function printZoom(changeBy) { /* handle +/- buttons for print zoom */
    var currentZoom = document.getElementById("printerzoom").value * 1;
    document.getElementById("printerzoom").value = currentZoom + changeBy;
    updateCalibrationInfo();
}

function printShift(changeBy) { /* handle +/- buttons for print shift */
    var currentShift = document.getElementById("printershift").value * 1;
    document.getElementById("printershift").value = currentShift + changeBy;
    updateCalibrationInfo();
}

// apply the printer zoom & shift values as !important <head> styles
// thanks http://stackoverflow.com/questions/798535/changing-media-specific-css-properties-from-javascript
function updateCalibrationInfo() {

    var currentZoom = document.getElementById("printerzoom").value;
    var currentShift = document.getElementById("printershift").value;
    // first write values onto printable area for reference
    document.getElementById("calibrationinfo").innerHTML = "Zoom: " + currentZoom + " / Horizontal shift: " + currentShift;
    // now update the @print style accordingly
    var head = document.getElementsByTagName('head')[0];
    var printerStyle = document.createElement('style');
    printerStyle.setAttribute('type', 'text/css');
    printerStyle.setAttribute('media', 'print');
    printerStyle.appendChild(document.createTextNode('body { width: ' + (950 + (currentZoom * 10)) + 'px !important;} #main { left: ' + ((currentShift * 5)) + 'px !important;}'));

    if (inlineMediaStyle != null) {
        head.replaceChild(printerStyle, inlineMediaStyle)
    } else {
        head.appendChild(printerStyle);
    }
    inlineMediaStyle = printerStyle;
}

var printCounter = 0;
var landscapeAlert = 0;

function doPrint(myContext) { /* What to do anytime a print button is clicked */
    window.landscapeAlert++;
    updateCalibrationInfo();
    if (window.landscapeAlert == 1) {
        alert('Important note: Please configure your print settings to LANDSCAPE (wide) orientation.');
    }

    if (myContext == 'generate') {
        window.printCounter++;
        if (window.printCounter == 2) {
            alert(" *** WARNING *** WARNING *** WARNING *** \n\nYou are about to print a second wallet with the same set of keys. \n\nUnless you intended to make a backup wallet, cancel this print job and generate a fresh set of keys.");
        }
    }
    window.print();
}

function testAndApplyVanityKey() { /* Verify that a self-entered key is valid, and compute the corresponding public address, render the wallet. */
    var suppliedKey = document.getElementById('suppliedPrivateKey').value;
    suppliedKey = suppliedKey.trim(); // in case any spaces or whitespace got pasted in
    document.getElementById('suppliedPrivateKey').value = suppliedKey;
    if (!ninja.privateKey.isPrivateKey(suppliedKey)) {
        var message = 'What you entered does not appear to be a ' + window.currencyName + ' Wallet Import Format (WIF) private key.';
        if (suppliedKey == null || suppliedKey.length == 0) {
            alert(message);
            return;
        }
        if (suppliedKey.length < ninja.wallets.paperwallet.minPassphraseLength) {
            alert(message + '\n\nIf you would like to use this text as a random data source or "brain wallet" passphrase, please supply a longer input.');
            return;
        }
        if (confirm(message + '\n\nClick OK to interpret this text as a random data source or "brain wallet" passphrase from which a SHA256 hash and keypair will be computed.')) {
            var wallet = new Bitcoin.ECKey(Crypto.SHA256(suppliedKey, {
                asBytes: true
            }));
            var computedPublicAddress = wallet.getBitcoinAddress();
            var privateKey = wallet.getBitcoinWalletImportFormat();
            ninja.wallets.paperwallet.buildManual({
                address: computedPublicAddress,
                wifKey: privateKey
            }, document.getElementById('paperpassphrase').value);
        }
    } else {
        var computedPublicAddress = new Bitcoin.ECKey(suppliedKey).getBitcoinAddress();
        alert('OK! This is a valid WIF-format private key whose public address is:\r\r' + computedPublicAddress);
        ninja.wallets.paperwallet.buildManual({
            address: computedPublicAddress,
            wifKey: suppliedKey
        }, document.getElementById('paperpassphrase').value);
        window.printCounter = 0;
    }
}

function guessPrinterSettings() {
    // detect browser / OS human-readable
    txt = "<p><small style=\"color: #666666;\"><b>User-agent:</b> " + navigator.userAgent + "</small><br />&nbsp;<br />";
    var parser = new UAParser();
    parser.setUA(navigator.userAgent);
    var result = parser.getResult();
    txt += result.browser.name + " version " + result.browser.version + " (" + result.engine.name + ")<br />";
    txt += result.os.name + " version " + result.os.version + " (" + result.cpu.architecture + ")<br />";
    txt += "</p>";
    document.getElementById("browserinfo").innerHTML = txt;

    // some common printer calibration settings here
    if (result.browser.name == 'Safari') { // OS X Safari
        document.getElementById("printerzoom").value = 5;
        document.getElementById("printershift").value = 6;
    } else if (result.browser.name == 'Chrome' && result.os.name == 'Mac OS X') {
        document.getElementById("printerzoom").value = 3;
        document.getElementById("printershift").value = 3;
    } else if (result.browser.name == 'Firefox' && result.os.name == 'Ubuntu') { // live CD?
        document.getElementById("printerzoom").value = 1;
        document.getElementById("printershift").value = 0;
    } else if (result.browser.name == 'Iceweasel' && result.os.name == 'Debian') {
        document.getElementById("printerzoom").value = 1.8;
        document.getElementById("printershift").value = 2.9;
    } else if (result.browser.name == 'IE' && result.os.name == 'Windows') {
        document.getElementById("printerzoom").value = 5;
        document.getElementById("printershift").value = 6;
    }

    updateCalibrationInfo();
}

function setDesign(whichDesign, isOnLoad, whichLanguage) {

    whichLanguage = typeof whichLanguage !== 'undefined' ? whichLanguage : 'english';

    // these translations are only applicable to the standard bitcoin wallet.
    // alt-currencies and special holiday/theme designs use JPGs instead.

    var translations = {
        'english': {
            publicAddress: "PUBLIC ADDRESS",
            depositVerify: "DEPOSIT / VERIFY",
            privateKey: "PRIVATE KEY",
            walletImportFormat: "WALLET IMPORT FORMAT",
            withdraw: "PRIVATE KEY / WITHDRAW",
            backLongTextFontSize: "9px",
            backPaperWallet: "BITCOIN PAPER WALLET",
            backAmount: "Amount Added",
            backDate: "Date",
            backNotes: "Notes:",
            backInst1: "To deposit additional funds to this paper wallet, send bitcoins to its public address, anytime.",
            backInst2: "Verify your balance by searching for the public address using a service such as blockchain.info",
            backInst3: "<strong>Do not reveal the private key</strong> until you are ready to import the balance on this wallet to a bitcoin client, exchange, or online wallet.",
            backInst4: "When withdrawing your funds from this wallet you should remove the <strong>ENTIRE BALANCE</strong>. If you attempt to spend only some of the funds you will likely lose the remaining bitcoins forever.<br />For instructions visit bitcoinpaperwallet.com",
        }
    };

    drawOpts.text = translations[whichLanguage];

    if (!whichDesign) whichDesign = 'default';

    // show the language menu if we're using the default design
    if (whichDesign == 'default') {
        document.getElementById('langPicker').style.display = 'inline';
    } else {
        document.getElementById('langPicker').style.display = 'none';
    }

    // if we used the dropdown menu to select an alt-coin, we need to reload the page altogether.
    if (!isOnLoad && whichDesign.substring(0, 4) == 'alt-') window.location = '?design=' + whichDesign;
    // if we're already using an altcoin, reload the page no matter which design we choose.
    if (!isOnLoad && window.networkVersion != 0x00) window.location = '?design=' + whichDesign;

    // reload background and add altcoin donation addresses if necessary
    if (isOnLoad && whichDesign.substring(0, 4) == 'alt-') {
        document.getElementById('logoback').style.backgroundImage = 'url(images/logo-' + whichDesign + '.png)';
    }

    // now deal with JPG-style backgrounds, and apply instructions to default design

    if (whichDesign == 'default') {
        var myFront = './images/front-300dpi.jpg';
        var myBack = './images/back-300dpi.jpg';
        var myPreview = './images/finished-sample-sealed.jpg';
        // setup proper back text translation & controls
        document.getElementById('backTextControl').style.display = 'block';
        document.getElementById('backText').style.display = 'block';
        document.getElementById("backPaperWallet").innerHTML = translations[whichLanguage]['backPaperWallet'];
        document.getElementById("backAmount").innerHTML = translations[whichLanguage]['backAmount'];
        document.getElementById("backDate").innerHTML = translations[whichLanguage]['backDate'];
        document.getElementById("backNotes").innerHTML = translations[whichLanguage]['backNotes'];
        document.getElementById("backLongText").style.fontSize = translations[whichLanguage]['backLongTextFontSize'];
        document.getElementById("backInst1").innerHTML = translations[whichLanguage]['backInst1'];
        document.getElementById("backInst2").innerHTML = translations[whichLanguage]['backInst2'];
        document.getElementById("backInst3").innerHTML = translations[whichLanguage]['backInst3'];
        document.getElementById("backInst4").innerHTML = translations[whichLanguage]['backInst4'];

    } else {
        var myFront = './images/front-300dpi-' + whichDesign + '.jpg';
        var myBack = './images/back-300dpi-' + whichDesign + '.jpg';
        var myPreview = './images/finished-sample-' + whichDesign + '.jpg';
        document.getElementById('backTextControl').style.display = 'none';
        document.getElementById('backText').style.display = 'none';
    }

    window.frontJPG = myFront;
    window.designChoice = whichDesign;

    // if the front artwork is ready, set it to either the correspondong JPG, or draw it
    if (document.getElementById('papersvg1') != null) {
        if (whichDesign == 'default') {
            document.getElementById("papersvg1").src = PaperWallet.draw.frontImage(document.getElementById("btcaddress1").innerHTML, drawOpts);
        } else {
            document.getElementById('papersvg1').src = myFront;
        }
    }

    // set back
    document.getElementById('backsvg1').src = myBack;
    document.getElementById('designPreview').src = myPreview;
    document.getElementById('designPicker').value = whichDesign; // force menu option in case it was picked during onload.
    document.getElementById('langPicker').value = whichLanguage;
}

function setDenomination(valueAndUnit) {
    if (!valueAndUnit) {
        document.getElementById('backSeal').style.display = 'none';
        document.getElementById('backDenominationOther').style.display = 'none';
    } else if (valueAndUnit == 'other') {
        document.getElementById('backDenominationOther').style.display = 'block';
        document.getElementById('backSeal').style.display = 'block';
        document.getElementById('backSealBig').innerHTML = document.getElementById('backDenominationOtherAmount').value;
        document.getElementById('backSealSmall').innerHTML = document.getElementById('backDenominationOtherUnit').value;
    } else {
        denominationParts = valueAndUnit.split(' ');
        document.getElementById('backSeal').style.display = 'block';
        document.getElementById('backSealBig').innerHTML = denominationParts[0];
        document.getElementById('backSealSmall').innerHTML = denominationParts[1];
        document.getElementById('backDenominationOther').style.display = 'none'
    }
}
