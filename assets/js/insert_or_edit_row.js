$(document).ready(function () {
  jQuery('.insert_row_input').on('change blur', function () {
    var tr = $(this).closest('tr'),
      checkbox = tr.find('.nullcheckbox');

    if ($(this).val() !== '') {
      checkbox.prop('checked', false);
    }
  });

  jQuery('input[type=submit]').on('mouseover', function () {
    jQuery('.insert_row_input').each(function () {
      var tr = $(this).closest('tr'),
        checkbox = tr.find('.nullcheckbox');

      if ($(this).val() !== '') {
        checkbox.prop('checked', false);
      }
    });
  });
});
