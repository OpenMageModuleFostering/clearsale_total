var chart;
var table;
var dataChart;
var dataTable;
var jsonTableData;
var loginToken;
var requestDashboard;
var apiKey = "947D7527-4276-4518-9F99-87D6561748CD";
var user = "arvatosystems";
var password = "M3WgSqES1Ppa";
var interval = 4;
var prefix = "https://sandbox.clearsale.com.br/";//"https://pci.clearsale.com.br/rest/";
var ApprovalChart;
var DeclineChart;
var SLAChart;
var SubmissionChart;
var ApprovalData;
var DeclineData;
var SLAData;
var SubmissionData;
var ApprovalRate;
var SubmissionCount = 0;
var AnalysisCount = 0;
var LastOrders;
var dataChart = { Data: [] };


jQuery(document).ready(function () {

    RequestLogin();
    BindChart();
    Chart();
    BindInfoGraph();
    MountTableLastOrders();

    jQuery("#ReportFilter").change(function () {
        interval = jQuery(this).val();
        RequestLogin();
        BindChart();
        ApprovalChart.setData(ApprovalData);
        DeclineChart.setData(DeclineData);
        SLAChart.setData(SLAData);
        SubmissionChart.setData(SubmissionData);
        BindInfoGraph();
        MountTableLastOrders();
    });


});

function logArrayElements(element, index, array) {

    var item = array[index];

    dataChart.Data.push({
        "label": item.Description,
        "value": item.Quantity
    });
}

function RequestLogin() {
    var requestLogin = {
        Login: {
            Apikey: apiKey,
            ClientID: user,
            ClientSecret: password
        }
    };

    requestLogin = JSON.stringify(requestLogin);

    console.log(requestLogin);

    jQuery.ajax({
        url: prefix + "api/auth/Login",
        type: "POST",
        contentType: "application/json",
        data: requestLogin,
        async: false,
        success: function (result) {
            loginToken = result.Token.Value;
        }, error: function (xhr, textStatus, errorThrown) {
            console.log(textStatus + ':' + errorThrown + ' | ' + xhr.responseText);
        }
    });

    console.log("token" + loginToken);

    requestDashboard = {
        LoginToken: loginToken,
        ApiKey: apiKey,
        Interval: interval,
        Status: "3"
    };

    requestDashboard = JSON.stringify(requestDashboard);

    console.log("Request" + requestDashboard);
}

function BindSubmissionCount() {
    jQuery.support.cors = true;
    console.log(requestDashboard);
    var jsonData = jQuery.ajax(
          {
              url: prefix + "Api/Report/CountSubmission",
              type: "POST",
              contentType: "application/json",
              data: requestDashboard,
              async: false
          }
          ).responseText;

    SubmissionCount = JSON.parse(jsonData);
}

function BindApprovalRate() {
    jQuery.support.cors = true;
    console.log(requestDashboard);
    var jsonData = jQuery.ajax(
          {
              url: prefix + "Api/Report/ApprovalRate",
              type: "POST",
              contentType: "application/json",
              data: requestDashboard,
              async: false
          }
          ).responseText;

    SubmissionCount = JSON.parse(jsonData);
}

function BindAnalysisCount() {
    jQuery.support.cors = true;
    requestDashboard.Status = 3;

    var jsonData = jQuery.ajax(
          {
              url: prefix + "Api/Report/CountByStatus",
              type: "POST",
              contentType: "application/json",
              data: requestDashboard,
              async: false
          }
          ).responseText;
    console.log("total_" + jsonData)
    AnalysisCount = JSON.parse(jsonData);
}

function BindLastOrders() {
    jQuery.support.cors = true;
    requestDashboard.Status = 3;

    var jsonData = jQuery.ajax(
          {
              url: prefix + "Api/Report/LastOrders",
              type: "POST",
              contentType: "application/json",
              data: requestDashboard,
              async: false
          }
          ).responseText;

    LastOrders = JSON.parse(jsonData);
}

function BindSubmission() {
    jQuery.support.cors = true;
    console.log(requestDashboard);
    var jsonData = jQuery.ajax(
          {
              url: prefix + "Api/Report/Approvals",
              type: "POST",
              contentType: "application/json",
              data: requestDashboard,
              async: false
          }
          ).responseText;
    console.log("total_" + jsonData)
    SubmissionData = JSON.parse(jsonData);
}

function BindReproved() {
    jQuery.support.cors = true;
    console.log(requestDashboard);
    var reprovedDataJson = jQuery.ajax(
     {
         url: prefix + "Api/Report/Reproved",
         type: "POST",
         contentType: "application/json",
         data: requestDashboard,
         async: false
     }
     ).responseText;

    console.log(reprovedDataJson);

    var reprovedData = JSON.parse(reprovedDataJson);

    dataChart = { Data: [] }

    reprovedData.forEach(logArrayElements);

    DeclineData = dataChart.Data;
}

function BindSLA() {
    jQuery.support.cors = true;

    var SLADataJson = jQuery.ajax(
     {
         url: prefix + "Api/Report/SLA",
         type: "POST",
         contentType: "application/json",
         data: requestDashboard,
         async: false
     }
     ).responseText;

    SLAData = JSON.parse(SLADataJson);
}

function BindApproved() {
    jQuery.support.cors = true;

    var orderStatusSummaryData = jQuery.ajax(
      {
          url: prefix + "Api/Report/OrderStatusSummary",
          type: "POST",
          contentType: "application/json",
          data: requestDashboard,
          async: false
      }
      ).responseText;

    dataChart = { Data: [] }

    var orderStatusSummary = JSON.parse(orderStatusSummaryData);

    orderStatusSummary.forEach(logArrayElements);

    ApprovalData = dataChart.Data;
}

function BindChart() {

    BindSubmission();
    BindReproved();
    BindSLA();
    BindApproved();
    BindSubmissionCount();
    BindApprovalRate();
    BindAnalysisCount();
    BindLastOrders();
}

function Chart() {
    SubmissionChart = new Morris.Area({
        element: 'morris-area-chart',
        data: SubmissionData,
        xkey: 'Data',
        ykeys: ['Aprovados', 'Quantidade'],
        labels: ['Approved', 'Total'],
        lineColors: ['#7C1952', '#FFA500'],
        pointSize: 2,
        hideHover: 'auto',
        resize: true
    });

    ApprovalChart = Morris.Donut({
        element: 'approved-chart',
        data: ApprovalData,
        colors: ['#009900', '#990000'],
        resize: true
    });

    DeclineChart = new Morris.Donut({
        element: 'reproved-chart',
        data: DeclineData,
        colors: ['#660000', '#990000', '#CC0000', '#FF0000'],
        resize: true
    });

    SLAChart = new Morris.Bar({
        element: 'morris-bar-chart',
        data: SLAData,
        xkey: 'Label',
        ykeys: ['Value'],
        labels: ['Total'],
        barColors: ['#FAA519'],
        barRatio: 0.4,
        xLabelAngle: 35,
        hideHover: 'auto',
        resize: true
    });
}

function BindInfoGraph() {
    jQuery("#Submited").html(SubmissionCount);
    jQuery("#ApprovalRate").html(ApprovalRate);
    jQuery("#InAnlaysis").html(AnalysisCount);
}

function MountTableLastOrders() {
    var html = "";

    jQuery.each(LastOrders, function (i, data) {
        // console.log(data.ClientOrderId + data.OrderActionData + data.Status);

        html += "<a href=" + "https://pci.clearsale.com.br/Appv4/Operacao/Analise/PedidoPorCodigoEntidade?codigoEntidade=" + data.ClientOrderId + "&entidadeID=10&visualizacao=true&t=" + loginToken + "&a=" + apiKey + " class=\"list-group-item\" target=\"_blank\">";
        html += "<span class=\"badge\">" + jQuery.timeago(data.OrderActionData) + "</span> <i class=\"fa fa-fw fa-check\"></i>" + data.ClientOrderId + " " + data.Status;
        html += "</a>";
    });

    jQuery("#lastorders").html(html);
}

