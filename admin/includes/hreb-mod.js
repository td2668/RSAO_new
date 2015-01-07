
//Knockout.js Model for an Ethics Modification
var Modification = function() {
    var self = this;
    self.sampleProductCategories = ko.observableArray([]);  // available update categories to select from
    $.getJSON('hreb-modify.php?action=getStatuses',
        function (json) {
                self.sampleProductCategories(json);
        });

    self.category = ko.observable();
    self.dateAdded = ko.observable(TodayString());
    self.notes = ko.observable();
};


var ModificationLog = function() {
    // Stores an array of modifications

    $.ajaxSetup({ cache: false });  // IE 9+ caches JSON results by default, so disable this.

    var self = this;
    self.mods = ko.observableArray([]); // new modifications
    self.existingMods = ko.observableArray([]);

    /** Load existing modifications from datbase **/
    getExistingMods();

    // Operations
    self.addLine = function() { self.mods.push(new Modification()) };
    self.removeLine = function(mod) { self.mods.remove(mod) };
    self.save = function() {
        var dataToSave = $.map(self.mods(), function(mod) {
            return mod.category() ? {
                ethicsNum: $('#ethicsNumber').val(),
                category: mod.category(),
                dateAdded: mod.dateAdded(),
                notes: mod.notes()
            } : undefined
        });
        saveMod(dataToSave);
        location.reload();  // reload the page so the new item shows in the list (might be able to use KO for this)
    }

    self.addMod = function() {
        self.mods.push(new Modification());
    }


    /******** Existing modification functions ************/

    function saveMod(dataToSave) {
        //$.post( "hreb-modify.php?action=savemod", JSON.stringify(dataToSave));
        $.ajax({
           type: 'POST',
           url: 'hreb-modify.php?action=savemod',
           data: JSON.stringify(dataToSave),
           async:false
     });
    getExistingMods();
    }

   function getExistingMods() {
       $.getJSON('hreb-modify.php?action=getexistingmods&ethicsnum=' + $('#ethicsNumber').val(),
            function (json) {
                $.each( json, function( key, val ) {
                        self.existingMods.push(val);
                });
            });
    };

    self.removeMod = function() {
        if(confirm("Are you sure you wish to remove this entry?")) {
            self.existingMods.remove(this);
            $.post( "hreb-modify.php?action=removemod&id=" + this.id);
        }
    }
};

    ko.applyBindings(new ModificationLog(), document.getElementById('modifications-wrapper'));


/**
 * Get today's date and time as a string
 *
 * @return {string} - today's date and time
 */
function TodayString() {
    var currentDate = new Date();
    var day = currentDate.getDate();
    var month = currentDate.getMonth() + 1;
    var year = currentDate.getFullYear();
    var hour = currentDate.getHours();
    var minutes = currentDate.getMinutes();
    var seconds = currentDate.getSeconds();

    return year + "-" + month + "-" + day + " " + hour + ":" + minutes + ":" + seconds;
}


