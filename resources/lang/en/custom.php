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
    'mail_failed' => 'Failed to sent the email notification for the expense claim. Please contact the admin.',
    

    //ACTION
    'canceled' => 'canceled',
    'submitted' => 'submitted',
    'add' => 'add',
    'edit' => 'edit',
    'delete' => 'delete',
    'approved' => 'approved',
    'rejected' => 'rejected',
    'revised' => 'revised',

    'user_dept_goa_not_found' => 'The head of department / GoA from the requestor is not found.',

    // DELEGATION
    'same_user_delegation_date' => 'The same user has conflict delegation with start date = :startDate and end date = :endDate.',


    // USER REQUEST
    'goa_user_not_found' => 'The user for GoA approval is not found.',
    'expense_claim_cant_status' => 'The expense claim status is :status and cannot be :action.',
    'expense_type_limit_daily' => 'The selected :attribute has conflict request with start date = :startDate and end date = :endDate.',
    'difference_date_request_invalid' => 'The month difference between current request date with the oldest request date must be less than 2 months.',
    'difference_date_request_submit_invalid' => 'The month difference between submit date and the oldest request date must be less than 2 months.',

    // USER REQUEST DETAIL
    'submit_confirm' => 'Are you sure you want to submit this item?',
    'submit_confirmation_not_title' => 'NOT submitted',
    'submit_confirmation_not_message' => "There's been an error. Your item might not have been submitted.",
    'expense_claim_list_empty' => 'The expense claim list is still empty.',
    'expense_claim_submit_success' => 'Successfully submit the expense claim to be approved.',
    'file_not_found' => 'File is not found.',
    'expense_claim_detail_cant_status' => 'The expense claim status is :status and cannot :action item from the list.',
    'expense_claim_detail_same_current' => 'The item in expense claim must have same currency with the others (:currency).',
    'cant_add_other_bod_level' => 'Cannot add new expense type with boD Approval = Yes and boD level = :level.',
    'cant_delete_other_bod_level' => 'Cannot delete expense type with boD Approval = Yes and boD level = :level.',
    'goa_user_limit_not_found' => 'The GoA with limit greater than :value is not found.',


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
    'model_has_relation' => 'The selected data has already had relation with other data.',

    // Message for log input user cron job
    'messages' => [
        'log' => "[:time] Line :line, Message: :message\n",
        'success' => "SUCCESS to input or update data",
        'failed' => "FAILED to input or update data",
        
    ],

    'business_purposes_restrict_level' => 'The business purposes approval can only be used for level :level.',
    'cant_change_expense_and_level' => 'The expense type or level cannot be changed because has already been used in User Request.',
    'exchange_date_invalid' => 'The selected date is out of range from the start and end exchange date (:start - :end).',
    'config_usd_invalid' => 'The configuration for USD to IDR / Start Exchange Date / End Exchange Date is not found.',
    'expense_claim_limit' => 'The total value must be greater than :bottom and less than or equal to :upper.'

];