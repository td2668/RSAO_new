$( document ).ready(function() {

    // When Modification button is clicked, load page
    $('#mod-button').click(function() {
        var ethicsnum = $('#ethicsNumber').val();
        window.location.href = "hreb-modify.php?action=mod&ethicsnum=" + ethicsnum;
    });

});

//Knockout.js Model for Tracking Form
var trackingFormViewModel = function () {
    var self = this;
    self.form_tracking_id = ko.observable();
    self.tracking_name = ko.observable("None Associated");
    self.synopsis = ko.observable("");
    self.submitted = ko.observable("");
    self.applicant = ko.observable("");
    self.pi = ko.observable("");
    self.department = ko.observable("");

    // Call loadJson() when the trackingId is changed.
    self.form_tracking_id.subscribe(function(newVal) {
        self.loadJson(newVal);
    });

    // A function to call that will load the JSON, using the given trackingId, with data from a remote call.
    self.loadJson = function (trackingId) {
        // Get Tracking From JSON
        $.getJSON('hreb-modify.php?action=getTracking', 'trackingId=' + trackingId,
            function (json) {
                //self.form_tracking_id("Tracking Id: " + json.form_tracking_id);
                self.tracking_name(json.tracking_name);
                self.synopsis(json.synopsis);
                self.submitted(json.niceSubmittedDate);
                self.applicant(json.applicant);
                if(json.isPI == 1) {
                    self.pi(json.applicant); // applicant is the PI
                    self.department(json.applicantDept);
                } else {
                    if(json.pi_id == 0) {
                        self.pi(json.piexternal); // external PI
                        self.department("External");
                    } else {
                        self.pi(json.pi) // applicant isn't the PI, someone else is
                        self.department(json.piDept);
                    }
                }
            });
    };


}

var trackingFormObject = new trackingFormViewModel();
ko.applyBindings(trackingFormObject, document.getElementById('hrebForm'));


