<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Razorpay\Api\Api;
use App\Models\Parents;
use App\Models\FeesPaid;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\FeesChoiceable;
use App\Models\UserNotification;
use App\Models\PaidInstallmentFee;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebhookController extends Controller
{
    //razorpay webhooks
    public function razorpay(Request $request)
    {
        try {
            Log::error("Razorpay Webhook");
            // get the json data of payment
            $webhookBody = $request->getContent();
            $webhookBody = file_get_contents('php://input');
            $data = json_decode($webhookBody);

            // $optional_paid_data = $data->payload->payment->entity->notes->optional_fees_paid;
            // $installment_paid_data = $data->payload->payment->entity->notes->installment_fees_paid;

            // gets the signature from header
            $webhookSignature = $request->header('X-Razorpay-Signature');
            $webhookSecret = env('RAZORPAY_WEBHOOK_SECRET');

            $api = new Api(env('RAZORPAY_API_KEY'), env('RAZORPAY_SECRET_KEY'));

            // get the metadata
            $parent_id = $data->payload->payment->entity->notes->parent_id;
            $student_id = $data->payload->payment->entity->notes->student_id;
            $class_id = $data->payload->payment->entity->notes->class_id;
            $session_year_id = $data->payload->payment->entity->notes->session_year_id;
            $payment_transaction_id = $data->payload->payment->entity->notes->payment_transaction_id;
            $is_fully_paid = $data->payload->payment->entity->notes->is_fully_paid;
            $type_of_fee = $data->payload->payment->entity->notes->type_of_fee;
            $is_due_charges = $data->payload->payment->entity->notes->is_due_charges;
            $due_charges = $data->payload->payment->entity->notes->due_charges ?? null;
            $optional_paid_data = json_decode($data->payload->payment->entity->notes->optional_fees_paid) ?? null;
            $installment_paid_data = json_decode($data->payload->payment->entity->notes->installment_fees_paid) ?? null;

            // get the current today's date
            $current_date = Carbon::now()->format('Y-m-d');

            //get the payment_id
            $payment_id  = $data->payload->payment->entity->id;

            // Log::error(json_encode($data));

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            //if the transaction is success
            if (isset($data->event) && $data->event == 'payment.captured') {

                //checks the signature
                $expectedSignature = hash_hmac("SHA256", $webhookBody, $webhookSecret);
                Log::error("expectedSignature --->" . $expectedSignature);
                Log::error("Header Signature --->" . $webhookSignature);

                if ($expectedSignature == $webhookSignature) {
                    Log::error("Signature Matched --->");
                }
                $api->utility->verifyWebhookSignature($webhookBody, $webhookSignature, $webhookSecret);

                // udpate data in payment transaction table local
                $transaction_db = PaymentTransaction::find($payment_transaction_id);
                if (!empty($transaction_db)) {
                    Log::error("INSIDE TRANSACTION DB");
                    if ($transaction_db->status != 1) {
                        Log::error("INSIDE TRANSACTION DB STATUS");
                        //get the total amount from table
                        $total_amount = $transaction_db->total_amount;

                        //udpate the values in payment transaction
                        $transaction_db->payment_id = $payment_id;
                        $transaction_db->payment_status = 1;
                        $transaction_db->save();

                        // Add due charges of fully Paid Complusory Amount
                        if ($type_of_fee == 0 && $is_due_charges == 1) {
                            $add_due_charges = new FeesChoiceable();
                            $add_due_charges->student_id = $student_id;
                            $add_due_charges->class_id = $class_id;
                            $add_due_charges->is_due_charges = 1;
                            $add_due_charges->total_amount = $due_charges;
                            $add_due_charges->session_year_id = $session_year_id;
                            $add_due_charges->save();
                        }

                        if (isset($installment_paid_data) && !empty($installment_paid_data)) {
                            Log::info("Paid Installment Fee Status Updated");
                            foreach ($installment_paid_data as $row) {
                                $db =  PaidInstallmentFee::find($row);
                                if (!empty($db)) {
                                    if ($db->status != 1) {
                                        $db->status = 1;
                                        $db->save();
                                    }
                                    // Log::error("Installment status updated", ['id' => $db->id, 'status' => $db->status]);
                                }
                            }
                        } else {
                            Log::info('NO INSTALLMENT DATA');
                        }

                        if (isset($optional_paid_data) && !empty($optional_paid_data)) {
                            Log::info("Optional Fees Status Updated");
                            foreach ($optional_paid_data as $row) {
                                $db =  FeesChoiceable::find($row);
                                if (!empty($db)) {
                                    if ($db->status != 1) {
                                        $db->status = 1;
                                        $db->save();
                                    }
                                    // Log::error("FeesChoiceable status updated", ['id' => $db->id, 'status' => $db->status]);
                                }
                            }
                        } else {
                            Log::info('NO OPTIONAL DATA');
                        }

                        // add data in fees paid table local
                        $update_fees_paid_query = FeesPaid::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id]);
                        if ($update_fees_paid_query->count()) {
                            $update_fee_paid_data = FeesPaid::findOrFail($update_fees_paid_query->first()->id);
                            $update_fee_paid_data->total_amount = ($update_fees_paid_query->first()->total_amount + $total_amount);
                            $update_fee_paid_data->is_fully_paid = $is_fully_paid;
                            $update_fee_paid_data->save();
                        } else {
                            $fees_paid_db = new FeesPaid();
                            $fees_paid_db->parent_id = $parent_id;
                            $fees_paid_db->student_id = $student_id;
                            $fees_paid_db->class_id = $class_id;
                            $fees_paid_db->total_amount = $total_amount;
                            $fees_paid_db->date = $current_date;
                            $fees_paid_db->session_year_id = $session_year_id;
                            $fees_paid_db->is_fully_paid = $is_fully_paid;
                            $fees_paid_db->due_charges = $due_charges ?? null;
                            $fees_paid_db->save();
                        }

                        http_response_code(200);

                        $user = Parents::where('id', $parent_id)->pluck('user_id');
                        $body = 'Amount :- ' . $total_amount;
                        $type = 'online';
                        $image = null;
                        $userinfo = null;

                        $notification = new Notification();
                        $notification->send_to = 2;
                        $notification->session_year_id = $session_year_id;
                        $notification->title = 'Payment Success';
                        $notification->message = $body;
                        $notification->type = $type;
                        $notification->date = Carbon::now();
                        $notification->is_custom = 0;
                        $notification->save();
                        foreach ($user as $data) {
                            $user_notification = new UserNotification();
                            $user_notification->notification_id = $notification->id;
                            $user_notification->user_id = $data;
                            $user_notification->save();
                        }

                        sendSimpleNotification($user, 'Payment Success', $body, $type, $image, $userinfo);
                    } else {
                        Log::error("Transaction Already Successed --->");
                        return false;
                    }
                } else {
                    Log::error("Payment Transaction id not found --->");
                    return false;
                }
            }

            //if the transaction is failed
            if (isset($data->event) && $data->event == 'payment.failed') {
                $transaction_db = PaymentTransaction::find($payment_transaction_id);
                if (!empty($transaction_db)) {
                    $total_amount = $transaction_db->total_amount;
                    $transaction_db->payment_id = $payment_id;
                    $transaction_db->payment_status = 0;
                    $transaction_db->save();
                    http_response_code(400);

                    FeesChoiceable::where('payment_transaction_id', $payment_transaction_id)->where('status', 0)->delete();
                    PaidInstallmentFee::where('payment_transaction_id', $payment_transaction_id)->where('status', 0)->delete();

                    $user = Parents::where('id', $parent_id)->pluck('user_id');
                    $body = 'Amount :- ' . $total_amount;
                    $type = 'online';
                    $image = null;
                    $userinfo = null;

                    $notification = new Notification();
                    $notification->send_to = 2;
                    $notification->session_year_id = $session_year_id;
                    $notification->title = 'Payment Failed';
                    $notification->message = $body;
                    $notification->type = $type;
                    $notification->date = Carbon::now();
                    $notification->is_custom = 0;
                    $notification->save();

                    foreach ($user as $data) {
                        $user_notification = new UserNotification();
                        $user_notification->notification_id = $notification->id;
                        $user_notification->user_id = $data;
                        $user_notification->save();
                    }
                    sendSimpleNotification($user, 'Payment Failed', $body, $type, $image, $userinfo);
                } else {
                    Log::error("Payment Transaction id not found --->");
                    return false;
                }
            } else {
                Log::error('Failed Else');
            }
        } catch (\Throwable $th) {
            Log::error($th);
            Log::error('Razorpay --> Webhook Error Accured');
        }
    }
    public function stripe()
    {
        // This is your test secret API key.
        // $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));

        // You can find your endpoint's secret in your webhook settings
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;
        // Verify webhook signature and extract the event.
        // See https://stripe.com/docs/webhooks/signatures for more information.
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            echo "Payload Mismatch";
            Log::error("Payload Mismatch");
            http_response_code(400);
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            echo "Signature Verification Failed";
            Log::error("Signature Verification Failed");
            http_response_code(400);
            exit();
        }


        // get the metadata
        $student_id = $event->data->object->metadata->student_id;
        $class_id = $event->data->object->metadata->class_id;
        $parent_id = $event->data->object->metadata->parent_id;
        $session_year_id = $event->data->object->metadata->session_year_id;
        $payment_transaction_id = $event->data->object->metadata->payment_transaction_id;
        $is_fully_paid = $event->data->object->metadata->is_fully_paid;
        $type_of_fee = $event->data->object->metadata->type_of_fee;
        $is_due_charges = $event->data->object->metadata->is_due_charges;
        $due_charges = $event->data->object->metadata->due_charges ?? null;
        $optional_paid_data = json_decode($event->data->object->metadata->optional_fees_paid) ?? null;
        $installment_paid_data = json_decode($event->data->object->metadata->installment_fees_paid) ?? null;

        //get the current today's date
        $current_date = Carbon::now()->format('Y-m-d');
        Log::error("event_type", [$event->type]);
        // handle the events
        switch ($event->type) {
            case 'payment_intent.succeeded':

                // update the values in transaction table local
                $transaction_db = PaymentTransaction::find($payment_transaction_id);
                if (!empty($transaction_db)) {
                    if ($transaction_db->status != 1) {

                        //get the total from transaction table local
                        $total_amount = $transaction_db->total_amount;

                        //udpate the values in transaction table local
                        $transaction_db->payment_status = 1;
                        $transaction_db->save();

                        // Add due charges of fully Paid Complusory Amount
                        if ($type_of_fee == 0 && $is_due_charges == 1) {
                            $add_due_charges = new FeesChoiceable();
                            $add_due_charges->student_id = $student_id;
                            $add_due_charges->class_id = $class_id;
                            $add_due_charges->is_due_charges = 1;
                            $add_due_charges->total_amount = $due_charges;
                            $add_due_charges->session_year_id = $session_year_id;
                            $add_due_charges->status = 1;
                            $add_due_charges->save();
                        }
                        if (isset($installment_paid_data) && !empty($installment_paid_data)) {
                            Log::info("Paid Installment Fee Status Updated");
                            foreach ($installment_paid_data as $row) {
                                $db =  PaidInstallmentFee::find($row);
                                if (!empty($db)) {
                                    if ($db->status != 1) {
                                        $db->status = 1;
                                        $db->save();
                                    }
                                    // Log::error("Installment status updated", ['id' => $db->id, 'status' => $db->status]);
                                }
                            }
                        } else {
                            Log::info('NO INSTALLMENT DATA');
                        }

                        if (isset($optional_paid_data) && !empty($optional_paid_data)) {
                            Log::info("Optional Fees Status Updated");
                            foreach ($optional_paid_data as $row) {
                                $db =  FeesChoiceable::find($row);
                                if (!empty($db)) {
                                    if ($db->status != 1) {
                                        $db->status = 1;
                                        $db->save();
                                    }
                                    // Log::error("FeesChoiceable status updated", ['id' => $db->id, 'status' => $db->status]);
                                }
                            }
                        } else {
                            Log::info('NO OPTIONAL DATA');
                        }

                        // add the data in fees paid table local
                        $update_fees_paid_query = FeesPaid::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id]);
                        if ($update_fees_paid_query->count()) {
                            $update_fee_paid_data = FeesPaid::findOrFail($update_fees_paid_query->first()->id);
                            $update_fee_paid_data->total_amount = ($update_fees_paid_query->first()->total_amount + $total_amount);
                            $update_fee_paid_data->is_fully_paid = $is_fully_paid;
                            $update_fee_paid_data->save();
                        } else {
                            $fees_paid_db = new FeesPaid();
                            $fees_paid_db->parent_id = $parent_id;
                            $fees_paid_db->student_id = $student_id;
                            $fees_paid_db->class_id = $class_id;
                            $fees_paid_db->payment_transaction_id = $payment_transaction_id;
                            $fees_paid_db->total_amount = $total_amount;
                            $fees_paid_db->date = $current_date;
                            $fees_paid_db->session_year_id = $session_year_id;
                            $fees_paid_db->is_fully_paid = $is_fully_paid;
                            $fees_paid_db->due_charges = $due_charges ?? null;
                            $fees_paid_db->save();
                        }

                        $user = Parents::where('id', $parent_id)->pluck('user_id');
                        $body = 'Amount :- ' . $total_amount;
                        $type = 'online';
                        $image = null;
                        $userinfo = null;

                        $notification = new Notification();
                        $notification->send_to = 2;
                        $notification->session_year_id = $session_year_id;
                        $notification->title = 'Payment Success';
                        $notification->message = $body;
                        $notification->type = $type;
                        $notification->date = Carbon::now();
                        $notification->is_custom = 0;
                        $notification->save();

                        foreach ($user as $data) {
                            $user_notification = new UserNotification();
                            $user_notification->notification_id = $notification->id;
                            $user_notification->user_id = $data;
                            $user_notification->save();
                        }

                        sendSimpleNotification($user, 'Payment Success', $body, $type, $image, $userinfo);
                        http_response_code(200);
                        break;
                    } else {
                        Log::error("Transaction Already Successed --->");
                        break;
                    }
                } else {
                    Log::error("Payment Transaction id not found --->");
                    break;
                }

            case 'payment_intent.payment_failed':
                // update the data in transaction table local
                $transaction_db = PaymentTransaction::find($payment_transaction_id);
                if (!empty($transaction_db)) {
                    $total_amount = $transaction_db->total_amount;
                    $transaction_db->payment_status = 0;
                    $transaction_db->save();
                    http_response_code(400);

                    FeesChoiceable::where('payment_transaction_id', $payment_transaction_id)->where('status', 0)->delete();
                    PaidInstallmentFee::where('payment_transaction_id', $payment_transaction_id)->where('status', 0)->delete();

                    $user = Parents::where('id', $parent_id)->pluck('user_id');
                    $body = 'Amount :- ' . $total_amount;
                    $type = 'online';
                    $image = null;
                    $userinfo = null;

                    $notification = new Notification();
                    $notification->send_to = 2;
                    $notification->session_year_id = $session_year_id;
                    $notification->title = 'Payment Failed';
                    $notification->message = $body;
                    $notification->type = $type;
                    $notification->date = Carbon::now();
                    $notification->is_custom = 0;
                    $notification->save();
                    foreach ($user as $data) {
                        $user_notification = new UserNotification();
                        $user_notification->notification_id = $notification->id;
                        $user_notification->user_id = $data;
                        $user_notification->save();
                    }

                    sendSimpleNotification($user, 'Payment Failed', $body, $type, $image, $userinfo);
                    break;
                } else {
                    Log::error("Payment Transaction id not found --->");
                    break;
                }

            default:
                echo "Unexpected event type";
                Log::error($event->type);
                // Unexpected event type
                Log::error('Received unknown event type');
                http_response_code(400);
                exit();
        }
    }

    public function paystack(Request $request)
    {
        try {
            $webhookBody = $request->getContent();
            $webhookBody = file_get_contents('php://input');

            $webhookSignature = $request->header('x-paystack-signature');
            $paystackSecretKey = env('PAYSTACK_SECRET_KEY');

            $expectedSignature = hash_hmac('sha512', $webhookBody, $paystackSecretKey);

            Log::error("Expected Signature --->" . $expectedSignature);
            Log::error("Header Signature --->" . $webhookSignature);

            // validate event do all at once to avoid timing attack
            if ($webhookSignature != $expectedSignature) {
                Log::error("Signature Not Matched --->");
                echo "Signature Not Matched";
                http_response_code(400);
                return false;
            }
            $current_date = Carbon::now()->format('Y-m-d');

            $event = json_decode($webhookBody);
            // Check if decoding was successful
            if ($event == null && !isset($event->data->metadata)) {
                // Access properties on the decoded object
                echo "Event Not Found";
                http_response_code(400);
                return false;
            }

            $payload = $event->data->metadata;
            Log::info("Payload*******", [$payload]);
            $payment_transaction_id = $payload->payment_transaction_id;
            $optional_paid_data = json_decode($payload->optional_fees_paid) ?? [];
            $installment_paid_data = json_decode($payload->installment_fees_paid) ?? [];

            $transaction_db = PaymentTransaction::find($payment_transaction_id);
            $student_id = $transaction_db->student_id;
            $parent_id = $transaction_db->parent_id;
            $class_id = $transaction_db->class_id;
            $session_year_id = $transaction_db->session_year_id;
            $is_fully_paid = $payload->is_fully_paid;
            $type_of_fee = $transaction_db->type_of_fee;
            $is_due_charges = $transaction_db->is_due_charges;
            $due_charges = $payload->due_charges;
            // $email = $payload->email;

            DB::beginTransaction();
            if ($event && isset($event->event)) {
                if ($event->event === 'charge.success') {
                    if (!empty($transaction_db)) {
                        if ($transaction_db->status != 1) {

                            //get the total from transaction table local
                            $total_amount = $transaction_db->total_amount;

                            //udpate the values in transaction table local
                            $transaction_db->order_id = $event->data->id;
                            $transaction_db->payment_status = 1;
                            $transaction_db->save();
                            Log::info("Update Payment Transaction Table");
                            // Add due charges of fully Paid Complusory Amount
                            if ($type_of_fee == 0 && $is_due_charges == 1) {
                                $add_due_charges = new FeesChoiceable();
                                $add_due_charges->student_id = $student_id;
                                $add_due_charges->class_id = $class_id;
                                $add_due_charges->is_due_charges = 1;
                                $add_due_charges->total_amount = $due_charges;
                                $add_due_charges->session_year_id = $session_year_id;
                                $add_due_charges->status = 1;
                                $add_due_charges->save();
                            }
                            if (isset($installment_paid_data) && !empty($installment_paid_data)) {
                                foreach ($installment_paid_data as $row) {
                                    $db =  PaidInstallmentFee::find($row);
                                    if (!empty($db)) {
                                        if ($db->status != 1) {
                                            $db->status = 1;
                                            $db->save();
                                            Log::info("Paid Installment Fee Status Updated");
                                        }
                                        Log::error("Installment status updated", ['id' => $db->id, 'status' => $db->status]);
                                    }
                                }
                            } else {
                                Log::info('NO INSTALLMENT DATA');
                            }

                            if (isset($optional_paid_data) && !empty($optional_paid_data)) {
                                foreach ($optional_paid_data as $row) {
                                    $db =  FeesChoiceable::find($row);
                                    if (!empty($db)) {
                                        if ($db->status != 1) {
                                            $db->status = 1;
                                            $db->save();
                                            Log::info("Optional Fees Status Updated");
                                        }
                                        Log::error("FeesChoiceable status updated", ['id' => $db->id, 'status' => $db->status]);
                                    }
                                }
                            } else {
                                Log::info('NO OPTIONAL DATA');
                            }

                            // add the data in fees paid table local
                            $update_fees_paid_query = FeesPaid::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id]);
                            if ($update_fees_paid_query->count()) {
                                Log::info('*********if', [$due_charges]);
                                $update_fee_paid_data = FeesPaid::findOrFail($update_fees_paid_query->first()->id);
                                $update_fee_paid_data->total_amount = ($update_fees_paid_query->first()->total_amount + $total_amount);
                                $update_fee_paid_data->is_fully_paid = $is_fully_paid;
                                $update_fee_paid_data->save();
                            } else {
                                Log::info('*********else', [$due_charges]);
                                $fees_paid_db = new FeesPaid();
                                $fees_paid_db->parent_id = $parent_id;
                                $fees_paid_db->student_id = $student_id;
                                $fees_paid_db->class_id = $class_id;
                                $fees_paid_db->payment_transaction_id = $payment_transaction_id;
                                $fees_paid_db->mode = 2;
                                $fees_paid_db->total_amount = $total_amount;
                                $fees_paid_db->date = $current_date;
                                $fees_paid_db->session_year_id = $session_year_id;
                                $fees_paid_db->is_fully_paid = $is_fully_paid;
                                $fees_paid_db->due_charges = $due_charges ?? null;
                                $fees_paid_db->save();
                            }

                            $user = Parents::where('id', $parent_id)->pluck('user_id');
                            $body = 'Amount :- ' . $total_amount;
                            $type = 'online';
                            $image = null;
                            $userinfo = null;

                            $notification = new Notification();
                            $notification->session_year_id = $session_year_id;
                            $notification->send_to = 2;
                            $notification->title = 'Payment Success';
                            $notification->message = $body;
                            $notification->type = $type;
                            $notification->date = Carbon::now();
                            $notification->is_custom = 0;
                            $notification->save();

                            foreach ($user as $data) {
                                $user_notification = new UserNotification();
                                $user_notification->notification_id = $notification->id;
                                $user_notification->user_id = $data;
                                $user_notification->save();
                            }
                            sendSimpleNotification($user, 'Payment Success', $body, $type, $image, $userinfo);
                            http_response_code(200);
                        } else {
                            Log::error("Transaction Already Successed --->");
                        }
                    } else {
                        Log::error("Payment Transaction id not found --->");
                    }
                }
            }
            echo "Payment Successfully Completed";
            http_response_code(200);
            DB::commit();
        } catch (Throwable $th) {

            http_response_code(400);
            echo "Error: " . $th->getMessage();

            // Handle exceptions if any
            DB::rollBack();
            throw $th;
        }
    }

    public function flutterwave(Request $request)
    {
        $body = $request->getContent();
        $body = file_get_contents('php://input');
        $data = json_decode($body);

        $signature = (isset($_SERVER['FLW_SECRET_HASH'])) ? $_SERVER['FLW_SECRET_HASH'] : '';
        // Your secret hash from environment variables
        $secretHash = env('FLW_SECRET_HASH');

        if ($signature !==  $secretHash) {
            exit();
        }
        // Retrieve the payment details
        $transactionId = $data->data->id;
        $status = $data->data->status;
        $metadata = $data->meta_data ?? [];
        $current_date = Carbon::now()->format('Y-m-d');
        $payment_transaction_id = $metadata->payment_transaction_id ?? null;
        $student_id = $metadata->student_id;
        $class_id = $metadata->class_id;
        $parent_id = $metadata->parent_id;
        $session_year_id = $metadata->session_year_id;
        $is_fully_paid = $metadata->is_fully_paid;
        $type_of_fee = $metadata->type_of_fee;
        $is_due_charges = $metadata->is_due_charges ?? 0;
        $due_charges = $metadata->due_charges ?? '';
        $optional_paid_data = json_decode($metadata->optional_fees_paid, true);
        $installment_paid_data = json_decode($metadata->installment_fees_paid, true);


        if (!$payment_transaction_id) {
            Log::warning('Payment transaction ID not found in metadata');
            return response()->json(['status' => 'error', 'message' => 'Payment transaction ID missing'], 400);
        }

        $transactionDb = PaymentTransaction::find($payment_transaction_id);

        if (!$transactionDb) {
            Log::error('Payment transaction not found in database');
            return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
        }
        $transaction_db = PaymentTransaction::find($payment_transaction_id);
        DB::beginTransaction();
        if (!empty($transaction_db)) {
            Log::error("INSIDE TRANSACTION DB");
            if ($transaction_db->status != 1 && $status == "successful") {
                Log::error("INSIDE TRANSACTION DB STATUS");
                //get the total amount from table
                $total_amount = $transaction_db->total_amount;

                //udpate the values in payment transaction
                $transaction_db->order_id = $transactionId;
                $transaction_db->payment_status = 1;
                $transaction_db->save();

                // Add due charges of fully Paid Complusory Amount
                if ($type_of_fee == 0 && $is_due_charges == 1) {
                    $add_due_charges = new FeesChoiceable();
                    $add_due_charges->student_id = $student_id;
                    $add_due_charges->class_id = $class_id;
                    $add_due_charges->is_due_charges = 1;
                    $add_due_charges->total_amount = $due_charges;
                    $add_due_charges->session_year_id = $session_year_id;
                    $add_due_charges->save();
                }

                if (isset($installment_paid_data) && !empty($installment_paid_data)) {
                    Log::info("Paid Installment Fee Status Updated");
                    foreach ($installment_paid_data as $row) {
                        $db =  PaidInstallmentFee::find($row);
                        if (!empty($db)) {
                            if ($db->status != 1) {
                                $db->status = 1;
                                $db->save();
                            }
                        }
                    }
                } else {
                    Log::info('NO INSTALLMENT DATA');
                }

                if (isset($optional_paid_data) && !empty($optional_paid_data)) {
                    Log::info("Optional Fees Status Updated");
                    foreach ($optional_paid_data as $row) {
                        $db =  FeesChoiceable::find($row);
                        if (!empty($db)) {
                            if ($db->status != 1) {
                                $db->status = 1;
                                $db->save();
                            }
                            // Log::error("FeesChoiceable status updated", ['id' => $db->id, 'status' => $db->status]);
                        }
                    }
                } else {
                    Log::info('NO OPTIONAL DATA');
                }

                // add data in fees paid table local
                $update_fees_paid_query = FeesPaid::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id]);
                if ($update_fees_paid_query->count()) {
                    $update_fee_paid_data = FeesPaid::findOrFail($update_fees_paid_query->first()->id);
                    $update_fee_paid_data->total_amount = ($update_fees_paid_query->first()->total_amount + $total_amount);
                    $update_fee_paid_data->is_fully_paid = $is_fully_paid;
                    $update_fee_paid_data->save();
                } else {
                    $fees_paid_db = new FeesPaid();
                    $fees_paid_db->parent_id = $parent_id;
                    $fees_paid_db->student_id = $student_id;
                    $fees_paid_db->class_id = $class_id;
                    $fees_paid_db->payment_transaction_id = $payment_transaction_id ?? null;
                    $fees_paid_db->mode = 2;
                    $fees_paid_db->total_amount = $total_amount;
                    $fees_paid_db->date = $current_date;
                    $fees_paid_db->session_year_id = $session_year_id;
                    $fees_paid_db->is_fully_paid = $is_fully_paid;
                    $fees_paid_db->due_charges = $due_charges ?? null;
                    $fees_paid_db->save();
                }

                http_response_code(200);

                $user = Parents::where('id', $parent_id)->pluck('user_id');
                $body = 'Amount :- ' . $total_amount;
                $type = 'online';
                $image = null;
                $userinfo = null;

                $notification = new Notification();
                $notification->send_to = 2;
                $notification->session_year_id = $session_year_id;
                $notification->title = 'Payment Success';
                $notification->message = $body;
                $notification->type = $type;
                $notification->date = Carbon::now();
                $notification->is_custom = 0;
                $notification->save();
                foreach ($user as $data) {
                    $user_notification = new UserNotification();
                    $user_notification->notification_id = $notification->id;
                    $user_notification->user_id = $data;
                    $user_notification->save();
                }

                sendSimpleNotification($user, 'Payment Success', $body, $type, $image, $userinfo);

                Log::info("Payment Successfull");
            } else {
                Log::error("Transaction Already Successed --->");
                return response()->json(['status' => 'error', 'message' => 'Transaction Already Successed'], 400);
            }
            if ($transaction_db->status != 1 && $status == "failed") {
                $transaction_db = PaymentTransaction::find($payment_transaction_id);
                if (!empty($transaction_db)) {
                    $total_amount = $transaction_db->total_amount;
                    $transaction_db->payment_id = null;
                    $transaction_db->payment_status = 0;
                    $transaction_db->save();
                    http_response_code(400);

                    FeesChoiceable::where('payment_transaction_id', $payment_transaction_id)->where('status', 0)->delete();
                    PaidInstallmentFee::where('payment_transaction_id', $payment_transaction_id)->where('status', 0)->delete();

                    $user = Parents::where('id', $parent_id)->pluck('user_id');
                    $body = 'Amount :- ' . $total_amount;
                    $type = 'online';
                    $image = null;
                    $userinfo = null;

                    $notification = new Notification();
                    $notification->send_to = 2;
                    $notification->session_year_id = $session_year_id;
                    $notification->title = 'Payment Failed';
                    $notification->message = $body;
                    $notification->type = $type;
                    $notification->date = Carbon::now();
                    $notification->is_custom = 0;
                    $notification->save();

                    foreach ($user as $data) {
                        $user_notification = new UserNotification();
                        $user_notification->notification_id = $notification->id;
                        $user_notification->user_id = $data;
                        $user_notification->save();
                    }
                    sendSimpleNotification($user, 'Payment Failed', $body, $type, $image, $userinfo);
                } else {
                    Log::error("Payment Transaction id not found --->");
                    return response()->json(['status' => 'error', 'message' => 'Payment Transaction id not found'], 404);
                }
            }
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Payment Successfully Completed'], 200);
        } else {
            Log::error("Payment Transaction id not found --->");
            return response()->json(['status' => 'error', 'message' => 'Payment Transaction id not found'], 404);
        }
    }

    public function paystackSuccessCallback()
    {
        $response = array(
            'error' => false,
            'message' => "Payment Successfully Completed",
            'code' => 200,
        );
        return response()->json($response);
    }

    public function flutterwaveSuccessCallback()
    {
        $response = array(
            'error' => false,
            'message' => "Payment Successfully Completed",
            'code' => 200,
        );
        return response()->json($response);
    }
}
