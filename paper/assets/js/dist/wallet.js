var Wallet = {
  seedLimit : 0,
  init : function() {
    this.setDefaults()
    this.listeners()
  },
  setDefaults : function() {
    this.seedLimit = ninja.seeder.seedLimit
    $('#mousemovelimit').text(this.seedLimit)
  },
  listeners : function() {
  }
}

Wallet.init();
