/****************************************************
 * * FOG Host Management - Edit - JavaScript
 *	Author:		Blackout
 *	Created:	9:34 AM 1/01/2012
 *	Revision:	$Revision$
 *	Last Update:	$LastChangedDate$
 ***/
var LoginHistory = $('#login-history');
var LoginHistoryDate = $('#loghist-date');
var LoginHistoryData = new Array();
var Labels = new Array();
var LabelData = new Array();
var LoginData = new Array();
var LoginDateMin = new Array();
var LoginDateMax = new Array();
function UpdateLoginGraph()
{
    $.ajax({
url: location.href.replace('edit','hostlogins'),
cache: false,
type: 'GET',
data: {
dte: LoginHistoryDate.val()
},
dataType: 'json',
success: UpdateLoginGraphPlot
});
}
function UpdateLoginGraphPlot(data) {
    // If nothing is available, nothing is returned
    if (data == null) return;
    // Initiate counter
    j =0;
    // Loop through the data
    for (i in data) {
        LoginDateMin = new Date(new Date(data[i]['min'] * 1000).getTime() - new Date(data[i]['min'] * 1000).getTimezoneOffset() * 60000);
        LoginDateMax = new Date(new Date(data[i]['max'] * 1000).getTime() - new Date(data[i]['max'] * 1000).getTimezoneOffset() * 60000);
        // Set the time intervals as they're only used for this iteration.
        LoginTime = new Date(new Date(data[i]['login'] * 1000).getTime() - new Date(data[i]['login'] * 1000).getTimezoneOffset() * 60000);
        LogoutTime = new Date(new Date(data[i]['logout'] * 1000).getTime() - new Date(data[i]['logout'] * 1000).getTimezoneOffset() * 60000);
        // Prepare the new items as necessary
        if (typeof(Labels) == 'undefined') {
            Labels = new Array();
            LabelData[i] = new Array();
            LoginData[i] = new Array();
        }
        // Does data exist for this item, if so place the data on the same line.
        if ($.inArray(data[i]['user'],Labels) > -1) {
            LoginData[i] = [LoginTime,$.inArray(data[i]['user'],Labels)+1,LogoutTime,data[i]['user']];
            // Otherwise create a new entry
        } else {
            Labels.push(data[i]['user']);
            LabelData[i] = [j+1,data[i]['user']];
            LoginData[i] = [LoginTime,++j,LogoutTime,data[i]['user']];
        }
    }
    LoginHistoryData = [{label: 'Logged In Time',data:LoginData}];
    var LoginHistoryOpts = {
colors: ['rgb(0,120,0)'],
        series: {
gantt: {
active:true,
       show:true,
       barHeight:.2
       }
        },
xaxis: {
min: LoginDateMin,
     max: LoginDateMax,
     tickSize: [2,'hour'],
     mode: 'time'
       },
yaxis: {
min: 0,
     max: LabelData.length + 1,
     ticks: LabelData,
       },
grid: {
hoverable: true,
           clickable: true,
      },
legend: {
position: "nw"
        }
    };
    $.plot(LoginHistory, LoginHistoryData, LoginHistoryOpts);
}
$(function() {
        $('#resetSecData').val('Reset Encryption Data');
        $('#resetSecData').click(function() {
            $('#resetSecDataBox').html('Are you sure you wish to reset this hosts encryption data?');
            $('#resetSecDataBox').dialog({
resizable: false,
modal: true,
title: 'Clear Encryption',
buttons: {
'Yes': function() {
$.ajax({
url: '../management/index.php',
type: 'POST',
timeout: 1000,
data: {
sub: 'clearAES',
id: $_GET['id'],
},
});
$(this).dialog('close');
},
'No': function() {
$(this).dialog('close');
}
}
});
});
UpdateLoginGraph();
// Uncheck default printer boxes.
$('input:checkbox[name="default"]').click(function() {
        var ischecked = $(this).attr('checked');
        $('input:checkbox').attr('checked',false);
        $(this).attr('checked', ischecked);
        });

// Fetch MAC Manufactors
$('.mac-manufactor').each(function()
        {
        var $this = $(this);
        var input = $this.parent().find('input');
        var mac = (input.size() ? input.val() : $this.parent().find('.mac').html());
        $this.load('../management/index.php?sub=getmacman&prefix=' + mac);
        });

// Remove MAC Buttons
$('.remove-mac').click(function()
        {
        //$(this).parent().remove();
        //$('.tipsy').remove();
        if ($('#additionalMACsCell').find('.additionalMAC').size() == 0)
        {
        $('#additionalMACsRow').hide();
        }
        //$(this).attr('checked', ischecked);
        });

// Add MAC Buttons - TODO: Rewrite OLD CODE
$('.add-mac').click(function()
        {
        $('#additionalMACsRow').show();
        $('#additionalMACsCell').append('<div><input class="additionalMAC" type="text" name="additionalMACs[]" /><i class="icon fa fa-minus-circle remove-mac hand" title="Remove MAC"></i><br/><span class="mac-manufactor"></span></div>');

        HookTooltips();

        return false;
        });

if ($('.additionalMAC').size())
{
    $('#additionalMACsRow').show();
}

// Show hide based on checked state.
$('#groupMeShow').is(':checked') ? $('#groupNotInMe').show() : $('#groupNotInMe').hide();
$('#printerNotInHost').is(':checked') ? $('#printerNotInHost').show() : $('#printerNotInHost').hide();
$('#SnapinNotInHost').is(':checked') ? $('#snapinNotInHost').show() : $('#snapinNotInHost').hide();
$('#groupMeShow').click(function() {
        $('#groupNotInMe').toggle();
        });
$('.toggle-checkbox1').click(function() {
        $('input.toggle-group1:checkbox').attr('checked', ($(this).attr('checked') ? 'checked' : false));
        });
$('.toggle-checkbox2').click(function() {
        $('input.toggle-group2:checkbox').attr('checked', ($(this).attr('checked') ? 'checked' : false));
        });
$('#hostPrinterShow').click(function() {
        $('#printerNotInHost').toggle();
        });
$('#hostSnapinShow').click(function() {
        $('#snapinNotInHost').toggle();
        });
$('.toggle-checkboxprint').click(function() {
        $('input.toggle-print:checkbox').attr('checked', ($(this).attr('checked') ? 'checked' : false));
        });
$('.toggle-checkboxsnapin').click(function() {
        $('input.toggle-snapin:checkbox').attr('checked', ($(this).attr('checked') ? 'checked' : false));
        });
});
