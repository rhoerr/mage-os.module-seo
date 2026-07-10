Title: [Major] State-changing admin actions lack HttpPostActionInterface; FAQ delete is invoked via GET link

**Severity: Major** (security hardening)

No admin controller in the module implements `HttpPostActionInterface`/`HttpGetActionInterface`, so all state-changing actions are reachable via GET routing: `Controller/Adminhtml/Faq/Save.php`, `Faq/Delete.php`, `Organisation/Save.php`, `Organisation/UploadLogo.php`.

`Faq/Delete` is *deliberately* invoked via GET: the grid actions column emits a plain href (`Ui/Component/Listing/Column/FaqActions.php:59-66`, no `'post' => true`) and the edit-form Delete button uses `deleteConfirm(url)` (`Block/Adminhtml/Faq/Edit/DeleteButton.php:27-31`). For GET requests, backend form-key validation does not apply; protection rests entirely on the admin URL secret key, which merchants can disable ("Add Secret Key to URLs").

The saves are effectively POST-only in practice (`getPostValue()` bails when empty: `Faq/Save.php:45`, `Organisation/Save.php:48`) and admin POSTs get form-key validation from `Magento\Backend\App\Action::dispatch` — but the interfaces should still be declared.

**Suggested fix:** implement `HttpPostActionInterface` on Save/Delete/UploadLogo, switch the grid delete action and delete button to POST with confirmation, and add server-side required-field validation on FAQ save (`Faq/Save.php:83-91` currently accepts empty identifier/question/answer and unvalidated `store_id`).

---
*Found during a code review assessing the module for potential Mage-OS bundling.*
