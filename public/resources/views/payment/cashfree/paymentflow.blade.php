<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width">
    <title>paymentFlow</title>
    <style>

        #loader{
            position: fixed;
            left: 0px;
            top: 0px;
            width: 100%;
            height: 100%;
            z-index: 9999;
            background: url('/images/login_loader.gif') 
                        50% 50% no-repeat rgb(249,249,249);
        }
    
    </style>
</head>

<body>

    <form id="redirectForm"  method="post" action="{{$url}}">
        <input type="hidden" id="appId" name="appId" value=""/>
        <input type="hidden" id="orderId" name="orderId" value=""/>
        <input type="hidden" id="orderAmount" name="orderAmount" value=""/>
        <input type="hidden" id="orderNote" name="orderNote" value=""/>
        <input type="hidden" id="customerName" name="customerName" value=""/>
        <input type="hidden" id="customerEmail" name="customerEmail" value="Johndoe@test.com"/>
        <input type="hidden" id="customerPhone" name="customerPhone" value="9999999999"/>
        <input type="hidden" id="returnUrl" name="returnUrl" value="<RETURN_URL>"/>
        <input type="hidden" id="notifyUrl" name="notifyUrl" value="<NOTIFY_URL>"/>
        <input type="hidden" id="signature" name="signature" value="<GENERATED_SIGNATURE>"/>
    </form>

    <div id="payment-div"></div>
    <div id="loader"></div>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <!-- <script src="https://www.gocashfree.com/assets/cashfree.sdk.v1.js" type="text/javascript"></script> -->
    <script src="https://www.cashfree.com/assets/cashfree.sdk.v1.2.js" type="text/javascript"></script>
    <!-- <script src="/js/cashfree.js" type="text/javascript"></script> -->

    

    <script>

        let uri = "{{route('paymentData', [$id, $payid])}}"
        function cancelPayment() {
            window.location.href = "{{route('cancel_cashfree')}}";
        }

        let postPaymentCallback = function (event) {
         
          return
          // Callback method that handles Payment 
          if (event.name == "PAYMENT_RESPONSE" && event.status == "SUCCESS") {
            // Handle Success
          } 
          else if (event.name == "PAYMENT_RESPONSE" && event.status == "CANCELLED") {
            // Handle Cancelled
            // cancelPayment()
          } 
          else if (event.name == "PAYMENT_RESPONSE" && event.status == "FAILED") {
            // Handle Failed
            // cancelPayment()
          } 
          else if (event.name == "VALIDATION_ERROR") { 
            // Incorrect inputs
            // cancelPayment()
          }
        }

        let callback = function (event) {
            console.log("event")
            let eventName = event.name;
            if (eventName == "PAYMENT_REQUEST" ) {
                if (event.status = "ERROR") {
                    cancelPayment()
                }
            }
            if (eventName != "PAYMENT_REQUEST") {
                if (event.response && event.response.txStatus != "SUCCESS") {
                    cancelPayment()
                }
            }
        }

        function displayPaymentForm(data){
            console.log(data)
            let config = {}
            config.layout = { view: 'inline', container: 'payment-div', width: '600' }
            config.mode = "{{$mode}}" //use PROD when you go live
            let response = CashFree.init(config)
            console.log(response)
            if (response.status == "OK") {
                CashFree.makePayment(data, callback)
            } else {
                cancelPayment()
            }
        }

        function submitForm(data) {
            document.getElementById('appId').value = data.appId
            document.getElementById('orderId').value = data.orderId
            document.getElementById('orderAmount').value = data.orderAmount
            document.getElementById('orderNote').value = data.orderNote
            document.getElementById('customerName').value = data.customerName
            document.getElementById('customerEmail').value = data.customerEmail
            document.getElementById('customerPhone').value = data.customerPhone
            document.getElementById('returnUrl').value = data.returnUrl
            document.getElementById('notifyUrl').value = data.notifyUrl
            document.getElementById('signature').value = data.signature
            document.getElementById('redirectForm').submit()
        }

        let fetchData = async ()  => {
            try {
                let response = await axios.get(uri)
                submitForm(response.data.data)
                // displayPaymentForm(response.data.data)
            } catch (error) {
                console.log(error);
                cancelPayment()
            }
        }

        fetchData()

    </script>
</body>
</html>
