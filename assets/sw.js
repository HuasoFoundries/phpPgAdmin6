self.addEventListener('install', function (/*event*/) {
  // The promise that skipWaiting() returns can be safely ignored.
  self.skipWaiting();

  // Perform any other actions required for your
  // service worker to install, potentially inside
  // of event.waitUntil();
});
self.addEventListener('activate', (event) => {
  event.waitUntil(clients.claim());
});
if (typeof workbox === 'undefined') {
  importScripts(
    'https://storage.googleapis.com/workbox-cdn/releases/5.1.2/workbox-sw.js'
  );
  workbox.loadModule('workbox-strategies');
  workbox.loadModule('workbox-cacheable-response');
  workbox.loadModule('workbox-expiration');
}
self.__precacheManifest = [].concat(self.__precacheManifest || []);
if (typeof workbox !== 'undefined' && workbox) {

  workbox.core.skipWaiting();

  workbox.core.clientsClaim();

  workbox.precaching.precacheAndRoute([
  {
    "url": "images/themes/bootstrap/Favicon.ico",
    "revision": "7f7f3b73b863ab0db2800b0cc9d87f22"
  },
  {
    "url": "images/themes/bootstrap/Introduction.png",
    "revision": "8dbc90cfbfd4d9a2f5a3e7c5924771ee"
  },
  {
    "url": "images/themes/bootstrap/logo.png",
    "revision": "6987da08785938281c0cef64a08e3263"
  },
  {
    "url": "images/themes/bootstrap/title.png",
    "revision": "6987da08785938281c0cef64a08e3263"
  },
  {
    "url": "images/themes/cappuccino/inputbckg.png",
    "revision": "0836efcda7efd8f7143e6bc944fb2ae1"
  },
  {
    "url": "images/themes/cappuccino/Lminus.png",
    "revision": "4c6ad5374b9518299739fb8f6c0f0dcf"
  },
  {
    "url": "images/themes/cappuccino/Lplus.png",
    "revision": "0e918eec776f591e565f252bbb3935d1"
  },
  {
    "url": "images/themes/cappuccino/openListe.png",
    "revision": "35fec4ef79e5c77f884c28db9d646ff8"
  },
  {
    "url": "images/themes/cappuccino/title.png",
    "revision": "256583a0062c189786a788f550e4833e"
  },
  {
    "url": "images/themes/cappuccino/Tminus.png",
    "revision": "fd64d87ca98273a08a689508c15e21d0"
  },
  {
    "url": "images/themes/cappuccino/Tplus.png",
    "revision": "6be509ad6d0bdf6f5f4bb0112a28a9c4"
  },
  {
    "url": "images/themes/default/AddArguments.png",
    "revision": "5783c8524fa7f4daff4f1ef7bcb4bfb9"
  },
  {
    "url": "images/themes/default/Admin.png",
    "revision": "a82687f9d571988c5a50cd1c4c85e245"
  },
  {
    "url": "images/themes/default/Aggregate.png",
    "revision": "ff0166451279bef06132ca55d2b13bb3"
  },
  {
    "url": "images/themes/default/Aggregates.png",
    "revision": "480dca5e5fa1bef15990e13f7299ac9a"
  },
  {
    "url": "images/themes/default/AllUsers.png",
    "revision": "c8e3e86ffd0a4d7370e08388352fcc53"
  },
  {
    "url": "images/themes/default/AvailableReplicationSet.png",
    "revision": "ce257e663eb495ba4d90fc55feb361ff"
  },
  {
    "url": "images/themes/default/AvailableSubscription.png",
    "revision": "5ec68e4a7bd37d101bdd202890bfb606"
  },
  {
    "url": "images/themes/default/Backup.png",
    "revision": "362dd1ab7684968a9051fb2ffd145a06"
  },
  {
    "url": "images/themes/default/blank.png",
    "revision": "ff372eacef6cd22aa9a962b1fab6b927"
  },
  {
    "url": "images/themes/default/Cast.png",
    "revision": "2b52864370d5111e7a68d3347f0047f4"
  },
  {
    "url": "images/themes/default/Casts.png",
    "revision": "5597d48b8d4b6d0e30762500ced49539"
  },
  {
    "url": "images/themes/default/CheckConstraint.png",
    "revision": "12878c160ad2030c33fbe74b9ef4980c"
  },
  {
    "url": "images/themes/default/Cluster.png",
    "revision": "8ffa29a8aadf2e8a78a58f246ecb8f3a"
  },
  {
    "url": "images/themes/default/Column.png",
    "revision": "e9a7cd147304fa43da5121aca3aa96bd"
  },
  {
    "url": "images/themes/default/Columns.png",
    "revision": "135c695556735ca7bfbec339300d475a"
  },
  {
    "url": "images/themes/default/Constraints.png",
    "revision": "a08d098a40f117173b627f279d3e5c84"
  },
  {
    "url": "images/themes/default/Conversion.png",
    "revision": "0f5b34e3477bc7dec9d214c75624618e"
  },
  {
    "url": "images/themes/default/Conversions.png",
    "revision": "34b35cebcc208e56a590de389650c31c"
  },
  {
    "url": "images/themes/default/Copy.png",
    "revision": "c8f58a82f94ad33c92c4434195211986"
  },
  {
    "url": "images/themes/default/CorruptedDatabase.png",
    "revision": "37d5edb86c49b49e04dc1f31ea61d944"
  },
  {
    "url": "images/themes/default/Cut.png",
    "revision": "13cb3db5b00eb488ea488c11f7ef6c7b"
  },
  {
    "url": "images/themes/default/Database.png",
    "revision": "f5db4650c1155434dd3395449145e185"
  },
  {
    "url": "images/themes/default/Databases.png",
    "revision": "f78fd91345a5f9e6135740c39f94c281"
  },
  {
    "url": "images/themes/default/Definition.png",
    "revision": "01e09c5e6a4dff39c69c69c30e3c2df0"
  },
  {
    "url": "images/themes/default/Delete.png",
    "revision": "909467ce82f287d29b32a3bde5001a98"
  },
  {
    "url": "images/themes/default/DisabledJob.png",
    "revision": "0c7afdc74e664afc728350efdc4f8e54"
  },
  {
    "url": "images/themes/default/DisconnectedDatabase.png",
    "revision": "871a020d4cce6841704c1e6465b5ac11"
  },
  {
    "url": "images/themes/default/DisconnectedServer.png",
    "revision": "0b2aecfe0a2fefbaa9d153e06a12ce4a"
  },
  {
    "url": "images/themes/default/Domain.png",
    "revision": "0573dfc5e4d10eb7d2708dc9be361e1d"
  },
  {
    "url": "images/themes/default/Domains.png",
    "revision": "5bc7b098fd0c94841c7bb39a8d32c3dd"
  },
  {
    "url": "images/themes/default/EnableArgument.png",
    "revision": "075f3f50ae58bea29ea9853ec69f8822"
  },
  {
    "url": "images/themes/default/Erase.png",
    "revision": "cd1bcdc76c1ac1c8d228b3748ed4b469"
  },
  {
    "url": "images/themes/default/Execute.png",
    "revision": "21388b26da2079d313b9df99324550ac"
  },
  {
    "url": "images/themes/default/ExecuteSave.png",
    "revision": "0e75ce0c09fce81f44bb6f71faf15fd8"
  },
  {
    "url": "images/themes/default/Explain.png",
    "revision": "e0e9aa509cbdd8ef731b35fa9a39aa84"
  },
  {
    "url": "images/themes/default/Export.png",
    "revision": "20d4e370edd5f9da50f168bedc708fe2"
  },
  {
    "url": "images/themes/default/Favicon.ico",
    "revision": "7f7f3b73b863ab0db2800b0cc9d87f22"
  },
  {
    "url": "images/themes/default/Filter.png",
    "revision": "6fa649f2cffe185328b703b4a5f69475"
  },
  {
    "url": "images/themes/default/ForeignKey.png",
    "revision": "928e06e119cc3a048912672d40d06abc"
  },
  {
    "url": "images/themes/default/Fts.png",
    "revision": "c13ce81b5e4a3187ce37a55a387cbd8e"
  },
  {
    "url": "images/themes/default/FtsCfg.png",
    "revision": "78a5c183c4ec2f68fa2c124f11df71ad"
  },
  {
    "url": "images/themes/default/FtsDict.png",
    "revision": "72795d6e7ba1eaa63f4353d075eb551c"
  },
  {
    "url": "images/themes/default/FtsParser.png",
    "revision": "512bc774fedde4b56563c935ea5b6a2d"
  },
  {
    "url": "images/themes/default/Function.png",
    "revision": "d1db8bbd46db17458812fd60ae458b4e"
  },
  {
    "url": "images/themes/default/Functions.png",
    "revision": "6c19e68bf4aff2345c62355ae814cd07"
  },
  {
    "url": "images/themes/default/GurusHint.png",
    "revision": "aee27759d60d8b19aea299c1af78c902"
  },
  {
    "url": "images/themes/default/Help.png",
    "revision": "e599e96f305c61cb374326d49834c990"
  },
  {
    "url": "images/themes/default/Histories.png",
    "revision": "dc9c5432bcca789f6e8adcac5fbecb4a"
  },
  {
    "url": "images/themes/default/History.png",
    "revision": "bb6651298f7feca614348a277417b679"
  },
  {
    "url": "images/themes/default/I.png",
    "revision": "7fef7f3891268fbd886d3776d4bb18d2"
  },
  {
    "url": "images/themes/default/Import.png",
    "revision": "591ac6f7a26a67ae9485c9ea5f53bfbe"
  },
  {
    "url": "images/themes/default/Index.png",
    "revision": "50d5f45d81511368d878756d6c26f47d"
  },
  {
    "url": "images/themes/default/Indexes.png",
    "revision": "68100f02b944c3e5814ad9ad679c0169"
  },
  {
    "url": "images/themes/default/Introduction.png",
    "revision": "8dbc90cfbfd4d9a2f5a3e7c5924771ee"
  },
  {
    "url": "images/themes/default/Job.png",
    "revision": "0b6670ea8590f013806ad04e139a4d9e"
  },
  {
    "url": "images/themes/default/Jobs.png",
    "revision": "7c5f31d0ac216e9f90c661e98dba2856"
  },
  {
    "url": "images/themes/default/Key.png",
    "revision": "932e67636e6acbb814a2dded608bb652"
  },
  {
    "url": "images/themes/default/L.png",
    "revision": "e151369d672d2e2cc6f647be2f736e8f"
  },
  {
    "url": "images/themes/default/Language.png",
    "revision": "20938113d067cff7a6ff773f75e07491"
  },
  {
    "url": "images/themes/default/Languages.png",
    "revision": "ac48fd0fdc93ad3f1928cccb9c9e028e"
  },
  {
    "url": "images/themes/default/Listen.png",
    "revision": "788ee2492ef829b80cfba68d56f14430"
  },
  {
    "url": "images/themes/default/Listens.png",
    "revision": "c529a6fda4b2e2a375f7e5f51e577120"
  },
  {
    "url": "images/themes/default/Lminus.png",
    "revision": "d25bce2a91933a53b40ec210c7323372"
  },
  {
    "url": "images/themes/default/Loading.gif",
    "revision": "af1d523a137ef9b2005411770f454b2e"
  },
  {
    "url": "images/themes/default/LowerArgument.png",
    "revision": "1f099fbed522a3980847f07bcc4573ae"
  },
  {
    "url": "images/themes/default/Lplus.png",
    "revision": "b42f13fbc6d7701cd1bb7d827c3113ca"
  },
  {
    "url": "images/themes/default/MViews.png",
    "revision": "a6cdad27aed2812de33ff2d45e00385b"
  },
  {
    "url": "images/themes/default/Node.png",
    "revision": "1c8e6ef452ff7a9770047b469c85a05c"
  },
  {
    "url": "images/themes/default/Nodes.png",
    "revision": "e3f4f6d43a689035a2208662e933f5ea"
  },
  {
    "url": "images/themes/default/ObjectNotFound.png",
    "revision": "8534caffcf3d558648a0662245e2fc3d"
  },
  {
    "url": "images/themes/default/OfferedReplicationSet.png",
    "revision": "adc078de3c9e353b75640c2e180d20e6"
  },
  {
    "url": "images/themes/default/OfferedSubscription.png",
    "revision": "768fc20465c6354398625c4ed5c85cb4"
  },
  {
    "url": "images/themes/default/Open.png",
    "revision": "92bb01a30f897095adb2f98c0036b9e9"
  },
  {
    "url": "images/themes/default/Operator.png",
    "revision": "fb73b5a1f6499159043e5b20dc3285db"
  },
  {
    "url": "images/themes/default/OperatorClass.png",
    "revision": "a9b82cc57bf520190315ddbc833e64c7"
  },
  {
    "url": "images/themes/default/OperatorClasses.png",
    "revision": "b94fc3a6153d8fb2e261ec454d69a252"
  },
  {
    "url": "images/themes/default/Operators.png",
    "revision": "c5cb1662b9cbf877de8cb08d0c8bc4cb"
  },
  {
    "url": "images/themes/default/Paste.png",
    "revision": "db9d9333b530c266bab387ae076a5ada"
  },
  {
    "url": "images/themes/default/Path.png",
    "revision": "f888ffe2c6ab9c59d45ee07ae6199f4a"
  },
  {
    "url": "images/themes/default/Paths.png",
    "revision": "737c12988806ad60b5a5aec244920709"
  },
  {
    "url": "images/themes/default/PrimaryKey.png",
    "revision": "d0744174f9db9929b5be155c9f603e01"
  },
  {
    "url": "images/themes/default/Privileges.png",
    "revision": "1f27241c0f520a279d3665028024b45e"
  },
  {
    "url": "images/themes/default/Processes.png",
    "revision": "bf5538bb0bd23ed8c209997766dac284"
  },
  {
    "url": "images/themes/default/Property.png",
    "revision": "9bfdb2725bc9facafe90474d2c364c8f"
  },
  {
    "url": "images/themes/default/RaiseArgument.png",
    "revision": "e7f18a3dea3acd7e3333cd8f95e3fcce"
  },
  {
    "url": "images/themes/default/Record.png",
    "revision": "acd2ad85dbbd2bf5b71c0062b167b139"
  },
  {
    "url": "images/themes/default/Records.png",
    "revision": "878ff904362c9b59e2d6f6ac93ecc4b8"
  },
  {
    "url": "images/themes/default/Redo.png",
    "revision": "a169b03df76e87e44c45757f6a168e61"
  },
  {
    "url": "images/themes/default/Refresh.png",
    "revision": "cf51d096cc572377189c2bc884936b78"
  },
  {
    "url": "images/themes/default/RemoveArgument.png",
    "revision": "8d804ee75dfd7b8f3ac98b218a847d54"
  },
  {
    "url": "images/themes/default/Replication.png",
    "revision": "cdd35ed8aa98e9b0d356effb0d74548b"
  },
  {
    "url": "images/themes/default/ReplicationSets.png",
    "revision": "7d5e0bff7082704171013afd322821d4"
  },
  {
    "url": "images/themes/default/Restore.png",
    "revision": "94b8007abc4354fd4cdbdbcd6087bea7"
  },
  {
    "url": "images/themes/default/Roles.png",
    "revision": "f1c25bb9e2a4eb52d9c6ea52545ede2d"
  },
  {
    "url": "images/themes/default/Rule.png",
    "revision": "565c4b37066f23c5a08d428f2592ee7b"
  },
  {
    "url": "images/themes/default/Rules.png",
    "revision": "18fed3c727afc31e40941828697047ad"
  },
  {
    "url": "images/themes/default/Save.png",
    "revision": "b5a94436bc66832e0498de05bd72b23e"
  },
  {
    "url": "images/themes/default/Schedule.png",
    "revision": "e2af41bef5d952cb6887723bc3e5921c"
  },
  {
    "url": "images/themes/default/Schedules.png",
    "revision": "2b7a924ed6a9074d4102a547772750bc"
  },
  {
    "url": "images/themes/default/Schema.png",
    "revision": "18b2e143c60c9ef3813032bb45310e67"
  },
  {
    "url": "images/themes/default/Schemas.png",
    "revision": "b9fe50541e03c0b6e503b4ac1e65ddf1"
  },
  {
    "url": "images/themes/default/Search.png",
    "revision": "9cf1fe46d6b8fd3ba257d46a3d05965d"
  },
  {
    "url": "images/themes/default/Sequence.png",
    "revision": "4bfa8c4668036788458732d85630f931"
  },
  {
    "url": "images/themes/default/Sequences.png",
    "revision": "c4bb32230118c69cf6bab8617ef62b16"
  },
  {
    "url": "images/themes/default/Server.png",
    "revision": "bf4a2c360541176e03505124830cddac"
  },
  {
    "url": "images/themes/default/Servers.png",
    "revision": "07681bcc17ebc43645d6c1796f5df219"
  },
  {
    "url": "images/themes/default/SqlEditor.png",
    "revision": "df73ed3b7b0a31d266d97f97d5ac5c5a"
  },
  {
    "url": "images/themes/default/Statistics.png",
    "revision": "b6d59a3d2b8ad767c54305bc91a8243a"
  },
  {
    "url": "images/themes/default/Step.png",
    "revision": "892be7a355b19ffdb0ce39483cd4b6c1"
  },
  {
    "url": "images/themes/default/Steps.png",
    "revision": "5f28c19fac73bc0e869caed580a28457"
  },
  {
    "url": "images/themes/default/Stop.png",
    "revision": "ef7050f86e5ace6f4c026057beca7b91"
  },
  {
    "url": "images/themes/default/Subscriptions.png",
    "revision": "065f6490b63a46edee2162a6a76a9131"
  },
  {
    "url": "images/themes/default/T.png",
    "revision": "fe196453a6e822e7ebbd23692b35fd5e"
  },
  {
    "url": "images/themes/default/Table.png",
    "revision": "edd7db39026ec7c3d78284a319198b8d"
  },
  {
    "url": "images/themes/default/Tables.png",
    "revision": "84533f1145926e2d71aafaa4ca1eb1ac"
  },
  {
    "url": "images/themes/default/Tablespace.png",
    "revision": "4014487f76df092d3f57b6a90a6c96a0"
  },
  {
    "url": "images/themes/default/Tablespaces.png",
    "revision": "7d2334e497eee6e5719b25dfa39cfe66"
  },
  {
    "url": "images/themes/default/title_blue.png",
    "revision": "6987da08785938281c0cef64a08e3263"
  },
  {
    "url": "images/themes/default/title.png",
    "revision": "27762f17ee6301aac5bb3521d34e142b"
  },
  {
    "url": "images/themes/default/Tminus.png",
    "revision": "eb42ed7ec1597a0a9976fb4198a7ee71"
  },
  {
    "url": "images/themes/default/Tplus.png",
    "revision": "fcfd867d96fa1908bd165c97f4099e74"
  },
  {
    "url": "images/themes/default/Trigger.png",
    "revision": "f827a98b8f30eefac6f9ab47d6ca1f16"
  },
  {
    "url": "images/themes/default/TriggerFunction.png",
    "revision": "9337acb5c8b57422cd0aab291aa706bb"
  },
  {
    "url": "images/themes/default/TriggerFunctions.png",
    "revision": "5dc7b60313a7a4f3598050afbddad7d2"
  },
  {
    "url": "images/themes/default/Triggers.png",
    "revision": "6644a0ddffc0487230295acd19de296c"
  },
  {
    "url": "images/themes/default/Type.png",
    "revision": "952a9dae51878f6869b24527bca77b49"
  },
  {
    "url": "images/themes/default/Types.png",
    "revision": "0f1e8651d2422952d224d1657442cc42"
  },
  {
    "url": "images/themes/default/Undo.png",
    "revision": "a60bb9589277159b06882458fc54e1a9"
  },
  {
    "url": "images/themes/default/UniqueConstraint.png",
    "revision": "96ce5e94257ba8115ffb5956e3ed3296"
  },
  {
    "url": "images/themes/default/User.png",
    "revision": "746e073e551917a29630184eace87c8e"
  },
  {
    "url": "images/themes/default/UserGroup.png",
    "revision": "735113096adc5e5a8299a6f6f09c721d"
  },
  {
    "url": "images/themes/default/UserGroups.png",
    "revision": "76c595fe16835a3bf582d0278828fc49"
  },
  {
    "url": "images/themes/default/Users.png",
    "revision": "6dda662436c3178b2a51c306edc1cc89"
  },
  {
    "url": "images/themes/default/Variables.png",
    "revision": "c5b6e5d13e4d159cf5893a0abb7cb9df"
  },
  {
    "url": "images/themes/default/View.png",
    "revision": "2479afdac6324e368251f9d436a86db0"
  },
  {
    "url": "images/themes/default/Views.png",
    "revision": "a8a50a05e97c59f0b6cbd8b123b0a4bd"
  },
  {
    "url": "images/themes/instaGIS/logo.png",
    "revision": "6987da08785938281c0cef64a08e3263"
  },
  {
    "url": "sw.dev.js",
    "revision": "5dea6508fde6f65581a3f467e457d123"
  },
  {
    "url": "themes/bootstrap/global.css",
    "revision": "97f9ebda1b8fb0e35ac35ffce8a5a960"
  },
  {
    "url": "themes/bootstrap/title.png",
    "revision": "6987da08785938281c0cef64a08e3263"
  },
  {
    "url": "themes/cappuccino/global.css",
    "revision": "faeff89daa358a9f481f42e32b77cfc3"
  },
  {
    "url": "themes/default/global.css",
    "revision": "c49fd9c93fd688a1445a17aa316b09bb"
  },
  {
    "url": "themes/global.css",
    "revision": "6cb6f33fdd8a22c780f6f968141c25a3"
  },
  {
    "url": "themes/gotar/global.css",
    "revision": "c5984fcad101f4b5507718d47780c026"
  },
  {
    "url": "vendor/codemirror/addon/comment/comment.js",
    "revision": "f2798285cd723a94866088997228accc"
  },
  {
    "url": "vendor/codemirror/addon/comment/continuecomment.js",
    "revision": "0374616c52803e22bace5dfabbc0e42f"
  },
  {
    "url": "vendor/codemirror/addon/dialog/dialog.css",
    "revision": "c89dce10b44d2882a024e7befc2b63f5"
  },
  {
    "url": "vendor/codemirror/addon/dialog/dialog.js",
    "revision": "904554fefae1a2beb0eaad467018af7a"
  },
  {
    "url": "vendor/codemirror/addon/display/autorefresh.js",
    "revision": "a8525e557b32a9ff330db311f444058b"
  },
  {
    "url": "vendor/codemirror/addon/display/fullscreen.css",
    "revision": "1a278e72b51528270f8ce9ec991929a1"
  },
  {
    "url": "vendor/codemirror/addon/display/fullscreen.js",
    "revision": "744a9a476b90075936f58ebb8b35ac85"
  },
  {
    "url": "vendor/codemirror/addon/display/panel.js",
    "revision": "45aa4eb1759d95e5169397df11a0cd79"
  },
  {
    "url": "vendor/codemirror/addon/display/placeholder.js",
    "revision": "0e8705231c3e7d910611e5cfcfc9145f"
  },
  {
    "url": "vendor/codemirror/addon/display/rulers.js",
    "revision": "38aaf61e611edfe39883f46f89b0b91b"
  },
  {
    "url": "vendor/codemirror/addon/edit/closebrackets.js",
    "revision": "7138da5915e3a819ea35126930b43402"
  },
  {
    "url": "vendor/codemirror/addon/edit/closetag.js",
    "revision": "eb8606363338598e8f3099392a7fa2ce"
  },
  {
    "url": "vendor/codemirror/addon/edit/continuelist.js",
    "revision": "cc1c2b9fd1184a4e8b845e4fb09707e1"
  },
  {
    "url": "vendor/codemirror/addon/edit/matchbrackets.js",
    "revision": "5a44e9d0ad6abe1afd67c48b70d1cbd2"
  },
  {
    "url": "vendor/codemirror/addon/edit/matchtags.js",
    "revision": "d0c67185e94d3a096299b680c0fca7d6"
  },
  {
    "url": "vendor/codemirror/addon/edit/trailingspace.js",
    "revision": "81d50700cee8c27e0e311de3650851bc"
  },
  {
    "url": "vendor/codemirror/addon/fold/brace-fold.js",
    "revision": "c4850e56d89da48a8625e13ed9a076db"
  },
  {
    "url": "vendor/codemirror/addon/fold/comment-fold.js",
    "revision": "9b289797886789d2ce7f40e3f7408c9e"
  },
  {
    "url": "vendor/codemirror/addon/fold/foldcode.js",
    "revision": "67922ad2e2c384c5455bfcacdec393d8"
  },
  {
    "url": "vendor/codemirror/addon/fold/foldgutter.css",
    "revision": "38bb68770b6f7ebaa7adea770a68e0b1"
  },
  {
    "url": "vendor/codemirror/addon/fold/foldgutter.js",
    "revision": "2e6a4ca9c0f057daaecbc330d8f96bc0"
  },
  {
    "url": "vendor/codemirror/addon/fold/indent-fold.js",
    "revision": "5017f49481f30946bd4229a6d05d5fcc"
  },
  {
    "url": "vendor/codemirror/addon/fold/markdown-fold.js",
    "revision": "6e3228db96bbadbb93c79922ecd88ce0"
  },
  {
    "url": "vendor/codemirror/addon/fold/xml-fold.js",
    "revision": "b88e73a8e7bdc8b032adfd5047dfe2c5"
  },
  {
    "url": "vendor/codemirror/addon/hint/anyword-hint.js",
    "revision": "736f4c0aa67db12cf39097f3d1790c8b"
  },
  {
    "url": "vendor/codemirror/addon/hint/css-hint.js",
    "revision": "4958c441a7cdf2d39fa6c8bd4b340168"
  },
  {
    "url": "vendor/codemirror/addon/hint/html-hint.js",
    "revision": "51364bfc06c261a20b9ced6606db9580"
  },
  {
    "url": "vendor/codemirror/addon/hint/javascript-hint.js",
    "revision": "fe11e9d0e373480aa61af6ae86c04dc1"
  },
  {
    "url": "vendor/codemirror/addon/hint/show-hint.css",
    "revision": "49647712414ff96d5846de9736b5dbd3"
  },
  {
    "url": "vendor/codemirror/addon/hint/show-hint.js",
    "revision": "2fa9552bd0d701cac634055d17bb130a"
  },
  {
    "url": "vendor/codemirror/addon/hint/sql-hint.js",
    "revision": "f66526ad34eaaeee2dca6282961808a8"
  },
  {
    "url": "vendor/codemirror/addon/hint/xml-hint.js",
    "revision": "65cf0f4e6ce510f4a189a0b87d943b8c"
  },
  {
    "url": "vendor/codemirror/addon/lint/coffeescript-lint.js",
    "revision": "140f1a44841627e860cafd955e89f4e7"
  },
  {
    "url": "vendor/codemirror/addon/lint/css-lint.js",
    "revision": "6a6166008cb94a6d62e05dc2cab7fc16"
  },
  {
    "url": "vendor/codemirror/addon/lint/html-lint.js",
    "revision": "4ce658b4b1c2cacb8a2685effa0ed4eb"
  },
  {
    "url": "vendor/codemirror/addon/lint/javascript-lint.js",
    "revision": "3db1c5d6629bde3e0a4a694c5cd94065"
  },
  {
    "url": "vendor/codemirror/addon/lint/json-lint.js",
    "revision": "dcfd194ca63e175996aaea2b3a58b598"
  },
  {
    "url": "vendor/codemirror/addon/lint/lint.css",
    "revision": "80cbf240f7114fb23e506da29cced118"
  },
  {
    "url": "vendor/codemirror/addon/lint/lint.js",
    "revision": "16098bd4d5e62f123cbc4482622e1360"
  },
  {
    "url": "vendor/codemirror/addon/lint/yaml-lint.js",
    "revision": "eabad9dfc99d98d6995c37fce194c28c"
  },
  {
    "url": "vendor/codemirror/addon/merge/merge.css",
    "revision": "d4009e57cbbb2c969147cf233671ba42"
  },
  {
    "url": "vendor/codemirror/addon/merge/merge.js",
    "revision": "001c710b643ececdbaf65328bb3419fc"
  },
  {
    "url": "vendor/codemirror/addon/mode/loadmode.js",
    "revision": "f8291cb1ca96e29af58def213012655a"
  },
  {
    "url": "vendor/codemirror/addon/mode/multiplex_test.js",
    "revision": "37963861dd6e41a530a6f84c2b17bdf7"
  },
  {
    "url": "vendor/codemirror/addon/mode/multiplex.js",
    "revision": "b132b004f352bf044cd80a9f55731147"
  },
  {
    "url": "vendor/codemirror/addon/mode/overlay.js",
    "revision": "4f8e4dcaeb7c237cbe4f1f69972cdc8a"
  },
  {
    "url": "vendor/codemirror/addon/mode/simple.js",
    "revision": "eed15e8b955aaee880af0c819b38fa25"
  },
  {
    "url": "vendor/codemirror/addon/runmode/colorize.js",
    "revision": "c8fb49ed714798e04bc3d0289b19efa4"
  },
  {
    "url": "vendor/codemirror/addon/runmode/runmode-standalone.js",
    "revision": "aa4a42bca69b6f096a18cd51203afe6e"
  },
  {
    "url": "vendor/codemirror/addon/runmode/runmode.js",
    "revision": "2c0ddde09a9f1f8f5dc9d4f67f03d2f2"
  },
  {
    "url": "vendor/codemirror/addon/runmode/runmode.node.js",
    "revision": "f3c8c9b85bbeecf3c873e52d3783f39b"
  },
  {
    "url": "vendor/codemirror/addon/scroll/annotatescrollbar.js",
    "revision": "87198e00de438bb7f9afe2e55b6cb4a3"
  },
  {
    "url": "vendor/codemirror/addon/scroll/scrollpastend.js",
    "revision": "92a24e9251be0dc620c11cb61919293f"
  },
  {
    "url": "vendor/codemirror/addon/scroll/simplescrollbars.css",
    "revision": "0352ba51fd6a422fe6cc44925e33ad88"
  },
  {
    "url": "vendor/codemirror/addon/scroll/simplescrollbars.js",
    "revision": "13948e6ef35e3c3d2c65de096af58721"
  },
  {
    "url": "vendor/codemirror/addon/search/jump-to-line.js",
    "revision": "cf5f2c65e9c8e26841923b5e1a6bf611"
  },
  {
    "url": "vendor/codemirror/addon/search/match-highlighter.js",
    "revision": "ace658d3aaf9d8ae9895ff97dd9eb5ca"
  },
  {
    "url": "vendor/codemirror/addon/search/matchesonscrollbar.css",
    "revision": "00ea2770c568a848190bcf52e4241276"
  },
  {
    "url": "vendor/codemirror/addon/search/matchesonscrollbar.js",
    "revision": "b7dfa5fd6a57e306bf7ce99542819883"
  },
  {
    "url": "vendor/codemirror/addon/search/search.js",
    "revision": "1e2c6bec0a25d4e7dea128f048b08931"
  },
  {
    "url": "vendor/codemirror/addon/search/searchcursor.js",
    "revision": "fa73eef9cf913ee2d766c8501a582a0c"
  },
  {
    "url": "vendor/codemirror/addon/selection/active-line.js",
    "revision": "30fc5d6c1847dc207bd0e38c0e97e789"
  },
  {
    "url": "vendor/codemirror/addon/selection/mark-selection.js",
    "revision": "c0240b66ae29bda93e80be5e60c9ed8d"
  },
  {
    "url": "vendor/codemirror/addon/selection/selection-pointer.js",
    "revision": "ae80c5e4d54f8ccb07c6373e66f523a4"
  },
  {
    "url": "vendor/codemirror/addon/tern/tern.css",
    "revision": "4d57ced774b5f3fa9f00dfa398e74819"
  },
  {
    "url": "vendor/codemirror/addon/tern/tern.js",
    "revision": "f6c76d9ec32faccbb8cf23b7a2f6f917"
  },
  {
    "url": "vendor/codemirror/addon/tern/worker.js",
    "revision": "6866b3b6f236f5ae8b89e15b5cf167e1"
  },
  {
    "url": "vendor/codemirror/addon/wrap/hardwrap.js",
    "revision": "359a2bb5a43f724a09f2adbbfe40dd86"
  },
  {
    "url": "vendor/codemirror/lib/codemirror.css",
    "revision": "fc217d502b05f65616356459c0ec1d62"
  },
  {
    "url": "vendor/codemirror/lib/codemirror.js",
    "revision": "82b9491f7e4ecd8ce57812ee3f99014f"
  },
  {
    "url": "vendor/codemirror/mode/meta.js",
    "revision": "3eb5b33a2d2022c4de839b1980511f54"
  },
  {
    "url": "vendor/codemirror/mode/sql/index.html",
    "revision": "91f74a33b9232251058426d1e16e9dce"
  },
  {
    "url": "vendor/codemirror/mode/sql/sql.js",
    "revision": "d21c7a0e713132429cf903be42310a89"
  },
  {
    "url": "vendor/datatables/datatables.min.css",
    "revision": "80026250946cff8a6fc0da995aa02566"
  },
  {
    "url": "vendor/datatables/datatables.min.js",
    "revision": "c1e3d63eab27d1aa7beb191d32340bba"
  },
  {
    "url": "vendor/datatables/images/sort_asc_disabled.png",
    "revision": "d7dc10c78f23615d328581aebcd805eb"
  },
  {
    "url": "vendor/datatables/images/sort_asc.png",
    "revision": "9326ad44ae4bebdedd141e7a53c2a730"
  },
  {
    "url": "vendor/datatables/images/sort_both.png",
    "revision": "9a6486086d09bb38cf66a57cc559ade3"
  },
  {
    "url": "vendor/datatables/images/sort_desc_disabled.png",
    "revision": "bda51e15154a18257b4f955a222fd66f"
  },
  {
    "url": "vendor/datatables/images/sort_desc.png",
    "revision": "1fc418e33fd5a687290258b23fac4e98"
  },
  {
    "url": "vendor/images/datatables/sort_asc_disabled.png",
    "revision": "d7dc10c78f23615d328581aebcd805eb"
  },
  {
    "url": "vendor/images/datatables/sort_asc.png",
    "revision": "9326ad44ae4bebdedd141e7a53c2a730"
  },
  {
    "url": "vendor/images/datatables/sort_both.png",
    "revision": "9a6486086d09bb38cf66a57cc559ade3"
  },
  {
    "url": "vendor/images/datatables/sort_desc_disabled.png",
    "revision": "bda51e15154a18257b4f955a222fd66f"
  },
  {
    "url": "vendor/images/datatables/sort_desc.png",
    "revision": "1fc418e33fd5a687290258b23fac4e98"
  },
  {
    "url": "vendor/jquery/images/ui-icons_444444_256x240.png",
    "revision": "f83a8b8886694eaef4505dd80af7a430"
  },
  {
    "url": "vendor/jquery/jquery-3.4.1.min.js",
    "revision": "220afd743d9e9643852e31a135a9f3ae"
  },
  {
    "url": "vendor/jquery/jquery-ui.min.css",
    "revision": "81188e0c65a0a25d5ebfa7356bf81884"
  },
  {
    "url": "vendor/jquery/jquery-ui.min.js",
    "revision": "8cbf62fc02083afe12a90787cb8f9e3c"
  },
  {
    "url": "vendor/jstree/jstree.js",
    "revision": "66cefd86e219c19be9a32b4a9f6f70b2"
  },
  {
    "url": "vendor/jstree/jstree.min.js",
    "revision": "dc4a6494cf51022fa4a8541d13837166"
  },
  {
    "url": "vendor/jstree/themes/default-dark/32px.png",
    "revision": "eebaf260766f5e0e773f53d3ea4f3e4d"
  },
  {
    "url": "vendor/jstree/themes/default-dark/40px.png",
    "revision": "51286e68b083696edaf4f9fc577e2a2d"
  },
  {
    "url": "vendor/jstree/themes/default-dark/style.css",
    "revision": "6791bf1b92e95d10c8445ac010c429df"
  },
  {
    "url": "vendor/jstree/themes/default-dark/style.min.css",
    "revision": "0236b108a8deddca3b0fae061147a0a6"
  },
  {
    "url": "vendor/jstree/themes/default-dark/throbber.gif",
    "revision": "62be6ed2b189444b472b8000dc187240"
  },
  {
    "url": "vendor/jstree/themes/default/32px.png",
    "revision": "db49c8de4f267eede40a9a8843efcdec"
  },
  {
    "url": "vendor/jstree/themes/default/40px.png",
    "revision": "1f075735090412ed7eb8077d819b19c6"
  },
  {
    "url": "vendor/jstree/themes/default/style.css",
    "revision": "0cf1fc2eadda48373db31569a791ae29"
  },
  {
    "url": "vendor/jstree/themes/default/style.min.css",
    "revision": "8f65ba57d02eadb0c75d0623190d1ee8"
  },
  {
    "url": "vendor/jstree/themes/default/throbber.gif",
    "revision": "9ed4669f524bec38319be63a2ee4ba26"
  },
  {
    "url": "vendor/jstree/themes/phppgadmin/32px.png",
    "revision": "230b82ca8561d9b4111ba8102cae2bb6"
  },
  {
    "url": "vendor/jstree/themes/phppgadmin/40px.png",
    "revision": "1f075735090412ed7eb8077d819b19c6"
  },
  {
    "url": "vendor/jstree/themes/phppgadmin/style.css",
    "revision": "f5a9ca92f2b904247c7b83ce4b054481"
  },
  {
    "url": "vendor/jstree/themes/phppgadmin/style.min.css",
    "revision": "8f65ba57d02eadb0c75d0623190d1ee8"
  },
  {
    "url": "vendor/jstree/themes/phppgadmin/throbber.gif",
    "revision": "9ed4669f524bec38319be63a2ee4ba26"
  },
  {
    "url": "vendor/less.min.js",
    "revision": "df377f04717c4d59bfdec987dff69a0e"
  },
  {
    "url": "vendor/select2/css/select2.css",
    "revision": "887b34f2cf309344f3e0b96aaab2b15d"
  },
  {
    "url": "vendor/select2/css/select2.min.css",
    "revision": "d44571114a90b9226cd654d3c7d9442c"
  },
  {
    "url": "vendor/select2/js/i18n/ar.js",
    "revision": "a8bb27ec698c86bde72c8a6f13a8e9b4"
  },
  {
    "url": "vendor/select2/js/i18n/az.js",
    "revision": "498dc667b34eb0fddc31c4e92330d1aa"
  },
  {
    "url": "vendor/select2/js/i18n/bg.js",
    "revision": "89cba4df3c8694fcb33098dd1646cac1"
  },
  {
    "url": "vendor/select2/js/i18n/ca.js",
    "revision": "2eaad4eb1950a0d542812c58d30c93dd"
  },
  {
    "url": "vendor/select2/js/i18n/cs.js",
    "revision": "a68bcd293adcd6d9ac0b8527c9b39189"
  },
  {
    "url": "vendor/select2/js/i18n/da.js",
    "revision": "cbf897a0ae53b0cffbbe3f50d8b1b136"
  },
  {
    "url": "vendor/select2/js/i18n/de.js",
    "revision": "366d0aacb55f4929cc50bb977abec674"
  },
  {
    "url": "vendor/select2/js/i18n/el.js",
    "revision": "5629ce65500f96c62414a27c6eaed62c"
  },
  {
    "url": "vendor/select2/js/i18n/en.js",
    "revision": "05649b26c08630d2b703bc1e9ef93c7b"
  },
  {
    "url": "vendor/select2/js/i18n/es.js",
    "revision": "dc9dbf9d65df3f69e6b6d650c97bd967"
  },
  {
    "url": "vendor/select2/js/i18n/et.js",
    "revision": "c3953fb90b6bb9669697f5f12e802a66"
  },
  {
    "url": "vendor/select2/js/i18n/eu.js",
    "revision": "11b925456433eaab07e35b8dca7046f5"
  },
  {
    "url": "vendor/select2/js/i18n/fa.js",
    "revision": "98e52839b583e1ca66f4360a4f43f9b0"
  },
  {
    "url": "vendor/select2/js/i18n/fi.js",
    "revision": "659847deefdcfd7e4f8f2ed924d360f4"
  },
  {
    "url": "vendor/select2/js/i18n/fr.js",
    "revision": "b06a3340de45535358a0bc33fa2b9739"
  },
  {
    "url": "vendor/select2/js/i18n/gl.js",
    "revision": "78a87f7c0a519118fbe4f583ff2a3b3f"
  },
  {
    "url": "vendor/select2/js/i18n/he.js",
    "revision": "222d90ee0344ee8beeb5fb1835c93c76"
  },
  {
    "url": "vendor/select2/js/i18n/hi.js",
    "revision": "116a90b7111b953cd092e30a034d6913"
  },
  {
    "url": "vendor/select2/js/i18n/hr.js",
    "revision": "e1d2c70b4df50d98d2c35856804d38be"
  },
  {
    "url": "vendor/select2/js/i18n/hu.js",
    "revision": "db45641f10b2412801d5872e40ef7c2f"
  },
  {
    "url": "vendor/select2/js/i18n/id.js",
    "revision": "6ee6c9c64b945bb8a0f42d247ee0d868"
  },
  {
    "url": "vendor/select2/js/i18n/is.js",
    "revision": "808c7d47acb59537728bc74fdeb0ad0d"
  },
  {
    "url": "vendor/select2/js/i18n/it.js",
    "revision": "bae1661dbb77c15384655faffc10a3fa"
  },
  {
    "url": "vendor/select2/js/i18n/ja.js",
    "revision": "19cf1ce8a03de84ea668e8fec99a8c80"
  },
  {
    "url": "vendor/select2/js/i18n/km.js",
    "revision": "6074a9c5575cfaa8b3c1dccdb3133dde"
  },
  {
    "url": "vendor/select2/js/i18n/ko.js",
    "revision": "74b17541834ff1bb8c5651d321bd2281"
  },
  {
    "url": "vendor/select2/js/i18n/lt.js",
    "revision": "a0783b1bd1594b7c584564cc68b6c6e5"
  },
  {
    "url": "vendor/select2/js/i18n/lv.js",
    "revision": "07fe2a580d17cba308a972fdabbcaea0"
  },
  {
    "url": "vendor/select2/js/i18n/mk.js",
    "revision": "4986d7fc3ff3ed9a5f8af646f5ca587b"
  },
  {
    "url": "vendor/select2/js/i18n/ms.js",
    "revision": "23e7b436957996a10f451bc8d688764d"
  },
  {
    "url": "vendor/select2/js/i18n/nb.js",
    "revision": "137e184004aaec03977a4caf1cca30f4"
  },
  {
    "url": "vendor/select2/js/i18n/nl.js",
    "revision": "c363ace8aa0501526c17a61ab2fb854f"
  },
  {
    "url": "vendor/select2/js/i18n/pl.js",
    "revision": "76465b54a6b0eb6b2204143a0827d0ca"
  },
  {
    "url": "vendor/select2/js/i18n/pt-BR.js",
    "revision": "9efbbac4fda8d23225df16dddecb2718"
  },
  {
    "url": "vendor/select2/js/i18n/pt.js",
    "revision": "5d6ccc53b347b155e1af6afb1bc5fe94"
  },
  {
    "url": "vendor/select2/js/i18n/ro.js",
    "revision": "1ddc2b9980dcdd1008761149e0349a8b"
  },
  {
    "url": "vendor/select2/js/i18n/ru.js",
    "revision": "d83609abf2e0ba927b9ec472bf47e180"
  },
  {
    "url": "vendor/select2/js/i18n/sk.js",
    "revision": "a0f1a818d09228a87ae105d09fdee80c"
  },
  {
    "url": "vendor/select2/js/i18n/sr-Cyrl.js",
    "revision": "2f3047aad49eedd75dd5dacc092a7e02"
  },
  {
    "url": "vendor/select2/js/i18n/sr.js",
    "revision": "157bc6eb978e9a35985bc655d09ac258"
  },
  {
    "url": "vendor/select2/js/i18n/sv.js",
    "revision": "2b21bb3f61100fd656b41d16e25e2f80"
  },
  {
    "url": "vendor/select2/js/i18n/th.js",
    "revision": "2a4ece4c4355b7efd9e9591a53b3edc1"
  },
  {
    "url": "vendor/select2/js/i18n/tr.js",
    "revision": "c1925d8817db211164145dc47b18d333"
  },
  {
    "url": "vendor/select2/js/i18n/uk.js",
    "revision": "3d56f311192daf9ce44246c52777789f"
  },
  {
    "url": "vendor/select2/js/i18n/vi.js",
    "revision": "3520aa7bdea8234161b2c18f631417a0"
  },
  {
    "url": "vendor/select2/js/i18n/zh-CN.js",
    "revision": "419002d3c6c10ec9618ce6275c1057d1"
  },
  {
    "url": "vendor/select2/js/i18n/zh-TW.js",
    "revision": "c021537edf2c555f149509150ff986e3"
  },
  {
    "url": "vendor/select2/js/select2.full.js",
    "revision": "a95323cb476000ee17d7a252786df963"
  },
  {
    "url": "vendor/select2/js/select2.full.min.js",
    "revision": "da607360bcc65284a197ada3d68d5439"
  },
  {
    "url": "vendor/select2/js/select2.js",
    "revision": "b8f26dd6733ccc6263cb273e8f821dab"
  },
  {
    "url": "vendor/select2/js/select2.min.js",
    "revision": "e87ca4c3554f7b9e693605ce12d3a234"
  }
]);

  console.log(`Yay! Workbox is loaded 🎉`);

  workbox.routing.registerRoute(
    /\/assets\/css/,
    new workbox.strategies.CacheFirst({
      cacheName: 'vendor-local-css',
      plugins: [
        new workbox.cacheableResponse.CacheableResponse({ statuses: [0, 200] }),
      ],
    })
  );
  workbox.routing.registerRoute(
    /\/assets\/js/,
    new workbox.strategies.CacheFirst({
      cacheName: 'vendor-local-js',
      plugins: [
        new workbox.cacheableResponse.CacheableResponse({ statuses: [0, 200] }),
      ],
    })
  );
  workbox.routing.registerRoute(
    /\/img/,
    new workbox.strategies.CacheFirst({
      cacheName: 'image-files',
      plugins: [
        new workbox.cacheableResponse.CacheableResponse({ statuses: [0, 200] }),

      })
  );

  // Cache the Google Fonts stylesheets with a stale-while-revalidate strategy.
  workbox.routing.registerRoute(
    /^https:\/\/fonts\.googleapis\.com/,
    new workbox.strategies.StaleWhileRevalidate({
      cacheName: 'google-fonts-stylesheets',
    })
  );

  // Cache the underlying font files with a cache-first strategy for 1 year.
  workbox.routing.registerRoute(
    /^https:\/\/fonts\.gstatic\.com/,
    new workbox.strategies.CacheFirst({
      cacheName: 'google-fonts-webfonts',
      plugins: [
        new workbox.cacheableResponse.CacheableResponse({ statuses: [0, 200] }),
      ],
    })
  );
} else {
  console.log(`Boo! Workbox didn't load 😬`);
}
