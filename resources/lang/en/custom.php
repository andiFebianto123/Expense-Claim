<?php

return [
    // GLOBAL
    'model_not_found' => 'The selected item is not found.',
    'error_permission_message' => "Unauthorized - You don't have the permission to access this page / do this action.",
    'token_invalid' => 'Token is invalid. Please try again.',
    'submit' => 'Submit',
    'approve' => 'Approve',
    'revise' => 'Revise',
    'reject' => 'Reject',
    

    //ACTION
    'canceled' => 'canceled',
    'submmited' => 'submitted',
    'add' => 'add',
    'edit' => 'edit',
    'delete' => 'delete',
    'approved' => 'approved',
    'rejected' => 'rejected',
    'revised' => 'revised',


    // USER REQUEST
    'goa_user_not_found' => 'The user for GoA approval is not found.',
    'expense_claim_cant_status' => 'The expense claim status is :status and cannot be :action.',

    // USER REQUEST DETAIL
    'submit_confirm' => 'Are you sure you want to submit this item?',
    'submit_confirmation_not_title' => 'NOT submitted',
    'submit_confirmation_not_message' => "There's been an error. Your item might not have been submitted.",
    'expense_claim_list_empty' => 'The expense claim list is still empty.',
    'expense_claim_submit_success' => 'Successfully submit the expense claim to be approved.',
    'file_not_found' => 'File is not found.',
    'expense_claim_detail_cant_status' => 'The expense claim status is :status and cannot :action item from the list.',
    'expense_claim_detail_same_current' => 'The item in expense claim must have same currency with the others (:currency).',


    // APPROVER HOD & GOA
    'approve_confirm' => 'Are you sure you want to approve this item?',
    'approve_confirmation_not_title' => 'NOT approved',
    'approve_confirmation_not_message' => "There's been an error. Your item might not have been approved.",
    'expense_claim_approve_success' => 'Successfully approve the expense claim.',

    'revise_confirm' => 'Are you sure you want to revise this item?',
    'revise_confirmation_not_title' => 'NOT revised',
    'revise_confirmation_not_message' => "There's been an error. Your item might not have been revised.",
    'expense_claim_revise_success' => 'Successfully revise the expense claim.',

    'reject_confirm' => 'Are you sure you want to reject this item?',
    'reject_confirmation_not_title' => 'NOT rejected',
    'reject_confirmation_not_message' => "There's been an error. Your item might not have been rejected.",
    'expense_claim_reject_success' => 'Successfully reject the expense claim.',
    'model_has_relation' => 'The selected data has already had relation with other data',

    // Message for log input user cron job
    'messages' => [
        'log' => "Line :line, data is :message\n",
        'success' => "SUCCESS to input or update",
        'failed' => "FAILED to input or update",
    ],

];