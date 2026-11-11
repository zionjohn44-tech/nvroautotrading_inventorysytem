<div class="inquiry-customer-picker form-field">
    <label for="Customer_ID">Customer details</label>
    <select id="Customer_ID" name="Customer_ID" aria-describedby="customer-help">
        <option value="">+ New customer — fill in the details below</option>
        <?php foreach ($inquiryCustomers as $customer): ?>
        <option value="<?= e($customer['Customer_ID']) ?>"<?= selected($data['Customer_ID'] ?? '', $customer['Customer_ID']) ?> data-details="<?= e(json_encode(array_intersect_key($customer, array_flip(['First_Name', 'Last_Name', 'Email', 'Contact_Number', 'Address'])), JSON_UNESCAPED_UNICODE)) ?>"><?= e($customer['First_Name'] . ' ' . $customer['Last_Name'] . ' · ' . $customer['Email']) ?></option>
        <?php endforeach; ?>
    </select>
    <small id="customer-help">Enter a new customer here or choose an existing customer to autofill their details. Changes to an existing customer update their shared contact record.</small>
</div>
