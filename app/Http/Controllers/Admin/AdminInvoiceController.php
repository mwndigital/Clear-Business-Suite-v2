<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InvoiceStoreRequest;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Notifications\AdminInvoiceSavedAndPublishedNotification;
use App\Notifications\AdminInvoiceSavedNotification;
use App\Notifications\ClientInvoiceNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class AdminInvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $invoices = Invoice::orderBy('invoice_date', 'asc')->paginate(20);
        return view('admin.pages.invoices.index', compact('invoices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clients = User::role('client')->get();
        $paymentMethods = PaymentMethod::orderBy('name', 'desc')->get();
        return view('admin.pages.invoices.create', compact('clients', 'paymentMethods'));
    }

    public function generateInvoiceNumber(){
        $lastRecord = Invoice::latest()->first();

        $invoiceNoPrefix = 'ABCD' ; //This will come from the settings table in the future for now its set to ABCD

        $offset = strlen($invoiceNoPrefix);

        //if no record in DB, set invoice no to 1
        if(!$lastRecord) {
            return $invoiceNoPrefix.'0001';
        }

        //Extract the number from last record
        $lastNumber = substr($lastRecord->invoice_number, $offset);

        $nextNumber = str_pad((int) $lastNumber + 1, 4, '0', STR_PAD_LEFT);

        return $invoiceNoPrefix . $nextNumber;

    }

    private function calculateLineItemsTotal($lineItems)
    {
        $total = 0;

        foreach ($lineItems as $item) {
            $quantity = isset($item['quantity']) ? (float) $item['quantity'] : 0;
            $price = isset($item['amount']) ? (float) $item['amount'] : 0;

            // Calculate subtotal for each line item
            $subtotal = $quantity * $price;

            // Add the subtotal to the total
            $total += $subtotal;
        }

        return $total;
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(InvoiceStoreRequest $request)
    {
        $invoiceNumber = $this->generateInvoiceNumber();

        $action = $request->input('submit_type');

        $lineItems = $request->input('lineItems', []);
        $lineItemsTotal = $this->calculateLineItemsTotal($request->input('lineItems', []));

        $taxAmount = $request->input('tax_amount', 0);

        $invoiceDiscount = $request->input('invoice_discount', 0);

        $totalAmount = $lineItemsTotal + $taxAmount - $invoiceDiscount;

        if($action == 'save'){
            $invoice = Invoice::create([
                'client_id' => $request->client_id,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'invoice_number' => $invoiceNumber,
                'payment_method' => $request->payment_method,
                'status' => 'draft',
                'invoice_discount' => $request->invoice_discount,
                'tax_amount' => $taxAmount,
                'sub_total' => $lineItemsTotal,
                'total' => $totalAmount,
            ]);

            foreach($request->input('lineItems', []) as $lineItemData){
                $lineItem = new InvoiceLineItem([
                    'description' => $lineItemData['description'],
                    'quantity' => $lineItemData['quantity'],
                    'amount' => $lineItemData['amount'],
                    'invoice_id' => $invoice->id
                ]);

                $invoice->invoiceLineItem()->save($lineItem);
            }

            //Setup the notification for admin
            $adminRoles = ['super admin', 'admin', 'staff'];
            $adminUsers = User::role($adminRoles)->get();

            foreach ($adminUsers as $adminUser) {
                $adminUser->notify(new AdminInvoiceSavedNotification($invoice));
            }

            return redirect('admin/invoices/edit/' .$invoice->id)->with('success', 'New invoice created successfully');
        }
        elseif($action == 'publish_and_send'){
            $invoice = Invoice::create([
                'client_id' => $request->client_id,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'invoice_number' => $invoiceNumber,
                'payment_method' => $request->payment_method,
                'status' => 'unpaid',
                'invoice_discount' => $request->invoice_discount,
                'tax_amount' => $taxAmount,
                'sub_total' => $lineItemsTotal,
                'total' => $totalAmount,
            ]);

            foreach($request->input('lineItems', []) as $lineItemData){
                $lineItem = new InvoiceLineItem([
                    'description' => $lineItemData['description'],
                    'quantity' => $lineItemData['quantity'],
                    'amount' => $lineItemData['amount'],
                    'invoice_id' => $invoice->id
                ]);

                $invoice->invoiceLineItem()->save($lineItem);
            }

            //Setup the notification for admin
            $adminRoles = ['super admin', 'admin', 'staff'];
            $adminUsers = User::role($adminRoles)->get();

            foreach ($adminUsers as $adminUser) {
                $adminUser->notify(new AdminInvoiceSavedAndPublishedNotification($invoice));
            }

            //Setup the notification for customer
            $client = User::find($invoice->client_id);
            $client->notify(new ClientInvoiceNotification($invoice));

            //Setup the email for customer


            return redirect('admin/invoices/edit/' .$invoice->id)->with('success', 'New invoice created successfully');
        }
        elseif($action == 'publish_and_record_payment'){

        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $invoice = Invoice::findOrFail($id);
        $lineItems = InvoiceLineItem::where('invoice_id', $invoice->id)->get();
        $paymentMethods = PaymentMethod::orderBy('name', 'desc')->get();

        return view('admin.pages.invoices.edit', compact('invoice', 'lineItems', 'paymentMethods'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
