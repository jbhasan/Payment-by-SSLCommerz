<?php

namespace Sayeed\PaymentBySslcommerz\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Sayeed\PaymentBySslcommerz\Library\SslCommerz\SslCommerzNotification;
use Illuminate\Support\Facades\DB;

class SslCommerzPaymentController extends Controller
{
	
	public function paymentResponse(Request $request, $response_type) {
		if ($response_type == 'success') {
			$response = $this->success($request);
			return redirect(url(config('sslcommerz.application_success_url') . '?data='.base64_encode(json_encode($response))));
		} elseif ($response_type == 'failed') {
			$response = $this->fail($request);
			return redirect(url(config('sslcommerz.application_failed_url') . '?data='.base64_encode(json_encode($response))));
		} elseif ($response_type == 'ipn') {
			$response = $this->ipn($request);
			return redirect(url(config('sslcommerz.application_success_url') . '?data='.base64_encode(json_encode($response))));
		} else {
			$response = $this->cancel($request);
			return redirect(url(config('sslcommerz.application_cancel_url') . '?data='.base64_encode(json_encode($response))));
		}
	}

    public function index(Request $request)
    {
		$requested_data = $request->all();
        # Here you have to receive all the order data to initate the payment.
        # Let's say, your oder transaction informations are saving in a table called "sslcommerz_orders"
        # In "sslcommerz_orders" table, order unique identity is "transaction_id". "status" field contain status of the transaction, "amount" is the order amount to be paid and "currency" is for storing Site Currency which will be checked with paid currency.

        $post_data = $requested_data;
        $post_data['total_amount'] = $post_data['amount']; # You cant not pay less than 10
        $post_data['currency'] = "BDT";
        $post_data['tran_id'] = uniqid(); // tran_id must be unique

        # CUSTOMER INFORMATION
        $post_data['cus_name'] = $requested_data['customer_name'];
        $post_data['cus_email'] = $requested_data['customer_email'];
        $post_data['cus_add1'] = $requested_data['customer_address'] ?? 'Customer Address';
        $post_data['cus_add2'] = "";
        $post_data['cus_city'] = "";
        $post_data['cus_state'] = "";
        $post_data['cus_postcode'] = "";
        $post_data['cus_country'] = $requested_data['customer_country'] ?? "Bangladesh";
        $post_data['cus_phone'] = $requested_data['customer_mobile'];
        $post_data['cus_fax'] = "";

        # SHIPMENT INFORMATION
        // $post_data['ship_name'] = "Store Test";
        // $post_data['ship_add1'] = "Dhaka";
        // $post_data['ship_add2'] = "Dhaka";
        // $post_data['ship_city'] = "Dhaka";
        // $post_data['ship_state'] = "Dhaka";
        // $post_data['ship_postcode'] = "1000";
        // $post_data['ship_phone'] = "";
        // $post_data['ship_country'] = "Bangladesh";

        $post_data['shipping_method'] = "NO";
        $post_data['product_name'] = $requested_data['product_name'] ?? "Digital Product";
        $post_data['product_category'] = "Goods";
        $post_data['product_profile'] = "Digital Product";

        # OPTIONAL PARAMETERS
        $post_data['value_a'] = $requested_data['value_a'] ?? "ref001";
        $post_data['value_b'] = $requested_data['value_b'] ?? "ref002";
        $post_data['value_c'] = $requested_data['value_b'] ?? "ref003";
        $post_data['value_d'] = $requested_data['value_b'] ?? "ref004";

        #Before  going to initiate the payment order status need to insert or update as Pending.
        DB::table('sslcommerz_orders')
            ->where('transaction_id', $post_data['tran_id'])
            ->updateOrInsert([
                'name' => $post_data['cus_name'],
                'email' => $post_data['cus_email'],
                'phone' => $post_data['cus_phone'],
                'amount' => $post_data['total_amount'],
                'status' => 'Pending',
                'address' => $post_data['cus_add1'],
                'transaction_id' => $post_data['tran_id'],
                'currency' => $post_data['currency']
            ]);

        $sslc = new SslCommerzNotification();
        # initiate(Transaction Data , false: Redirect to SSLCOMMERZ gateway/ true: Show all the Payement gateway here )
        $payment_options = $sslc->makePayment($post_data, 'hosted');

        if (!is_array($payment_options)) {
            print_r($payment_options);
            $payment_options = array();
        }
    }

    private function success(Request $request)
    {
        $tran_id = $request->input('tran_id');
        $amount = $request->input('amount');
        $currency = $request->input('currency');
        $sslc = new SslCommerzNotification();

        #Check order status in order tabel against the transaction id or order id.
        $order_details = DB::table('sslcommerz_orders')->where('transaction_id', $tran_id)->select('transaction_id', 'status', 'currency', 'amount')->first();
        if ($order_details->status == 'Pending') {
            $validation = $sslc->orderValidate($request->all(), $tran_id, $amount, $currency);
            if ($validation) {
                /*
                That means IPN did not work or IPN URL was not set in your merchant panel. Here you need to update order status
                in order table as Processing or Complete.
                Here you can also sent sms or email for successfull transaction to customer
                */
                DB::table('sslcommerz_orders')
                    ->where('transaction_id', $tran_id)
                    ->update(['status' => 'Complete', 'response_data' => base64_encode(json_encode($request->all()))]);
                return ["status" => "completed", "message" => "Transaction is successfully Completed"];
            }
        } else if ($order_details->status == 'Processing' || $order_details->status == 'Complete') {
            /*
             That means through IPN Order status already updated. Now you can just show the customer that transaction is completed. No need to udate database.
             */
            return ["status" => "completed", "message" => "Transaction is successfully Completed"];
        } else {
            #That means something wrong happened. You can redirect customer to your product page.
            return ["status" => "Invalid", "message" => "Invalid Transaction"];
        }
    }

    private function fail(Request $request)
    {
        $tran_id = $request->input('tran_id');
        $order_details = DB::table('sslcommerz_orders')
            ->where('transaction_id', $tran_id)
            ->select('transaction_id', 'status', 'currency', 'amount')->first();

        if ($order_details->status == 'Pending') {
            DB::table('sslcommerz_orders')
                ->where('transaction_id', $tran_id)
                ->update(['status' => 'Failed', 'response_data' => base64_encode(json_encode($request->all()))]);
				return ["status" => "failed", "message" => "Transaction is Failed"];
        } else if ($order_details->status == 'Processing' || $order_details->status == 'Complete') {
            return ["status" => "completed", "message" => "Transaction is already Successful"];
        } else {
            return ["status" => "invalid", "message" => "Transaction is Invalid"];
        }
    }

    private function cancel(Request $request)
    {
        $tran_id = $request->input('tran_id');
        $order_details = DB::table('sslcommerz_orders')
            ->where('transaction_id', $tran_id)
            ->select('transaction_id', 'status', 'currency', 'amount')->first();

        if ($order_details->status == 'Pending') {
            DB::table('sslcommerz_orders')
                ->where('transaction_id', $tran_id)
                ->update(['status' => 'Canceled', 'response_data' => base64_encode(json_encode($request->all()))]);
				return ["status" => "cancel", "message" => "Transaction is Cancel"];
        } else if ($order_details->status == 'Processing' || $order_details->status == 'Complete') {
            return ["status" => "completed", "message" =>  "Transaction is already Successful"];
        } else {
            return ["status" => "invalid", "message" => "Transaction is Invalid"];
        }
    }

    private function ipn(Request $request)
    {
        #Received all the payement information from the gateway
        if ($request->input('tran_id')) #Check transation id is posted or not.
        {
            $tran_id = $request->input('tran_id');
            #Check order status in order tabel against the transaction id or order id.
            $order_details = DB::table('sslcommerz_orders')
                ->where('transaction_id', $tran_id)
                ->select('transaction_id', 'status', 'currency', 'amount')->first();
            if ($order_details->status == 'Pending') {
                $sslc = new SslCommerzNotification();
                $validation = $sslc->orderValidate($request->all(), $tran_id, $order_details->amount, $order_details->currency);
                if ($validation == TRUE) {
                    /*
                    That means IPN worked. Here you need to update order status
                    in order table as Processing or Complete.
                    Here you can also sent sms or email for successful transaction to customer
                    */
                    DB::table('sslcommerz_orders')
                        ->where('transaction_id', $tran_id)
                        ->update(['status' => 'Complete', 'response_data' => base64_encode(json_encode($request->all()))]);
					return ["status" => "completed", "message" => "Transaction is successfully Completed"];
                }
            } else if ($order_details->status == 'Processing' || $order_details->status == 'Complete') {
                #That means Order status already updated. No need to udate database.
                return ["status" => "completed", "message" => "Transaction is already successfully Completed"];
            } else {
                #That means something wrong happened. You can redirect customer to your product page.
                return ["status" => "invalid", "message" => "Invalid Transaction"];
            }
        } else {
            return ["status" => "invalid", "message" => "Invalid Data"];
        }
    }

}
