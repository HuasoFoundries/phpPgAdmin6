    // Globals

    /*
     * Multiple Selection lists in HTML Document
     */
    var tableColumnList;
    var indexColumnList;

    /*
     * Two Array vars
     */
    var indexColumns,
        tableColumns;

    function buttonPressed(object) {

        if (object.name == "add") {
            from = tableColumnList;
            to = indexColumnList;
        } else {
            to = tableColumnList;
            from = indexColumnList;
        }

        var selectedOptions = getSelectedOptions(from);

        for (i = 0; i < selectedOptions.length; i++) {
            option = new Option(selectedOptions[i].text);
            addToArray(to, option);
            removeFromArray(from, selectedOptions[i].index);
        }
    }

    function doSelectAll() {
        for (var x = 0; x < indexColumnList.options.length; x++) {
            indexColumnList.options[x].selected = true;
        }
    }

    function init() {
        indexColumnList = document.getElementById("IndexColumnList");
        if (indexColumnList) {
            indexColumns = indexColumnList.options;
        }

        if (document.formIndex) {
            tableColumnList = document.formIndex.TableColumnList;
            tableColumns = tableColumnList.options;
        }

    }

    function getSelectedOptions(obj) {
        var selectedOptions = [];

        for (i = 0; i < obj.options.length; i++) {
            if (obj.options[i].selected) {
                selectedOptions.push(obj.options[i]);
            }
        }

        return selectedOptions;
    }

    function removeFromArray(obj, index) {
        obj.remove(index);
    }

    function addToArray(obj, item) {
        obj.options[obj.options.length] = item;
    }