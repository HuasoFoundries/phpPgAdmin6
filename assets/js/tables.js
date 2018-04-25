var predefined_lengths = null;
var sizesLength = false;

function checkLengths(sValue, idx) {
  if (predefined_lengths) {
    var uppercase_predefined_lengths = predefined_lengths.map(function (item) {
      return item.toString().toUpperCase();
    });
    // If the type has a predefined length on PostgreSQL, disable the length input field
    if (uppercase_predefined_lengths.indexOf(sValue.toString().toUpperCase()) !== -1) {
      document.getElementById("lengths" + idx).value = '';
      document.getElementById("lengths" + idx).disabled = 'on';
      return;
    }

    document.getElementById("lengths" + idx).disabled = '';
  }
}
