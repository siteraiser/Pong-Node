The Pong Server to be run at home to manage products and process transactions for the online <a href="https://github.com/siteraiser/Pong-Hub">Pong Hub</a> website.<br>
This is written in PHP and requires mysql or mariadb to run and is managed via ajax in a web browser.<br>


<p>To install, create a database named pong and place the project files in your htdocs / root php folder. The example db user is root with no password. You need to open your engram wallet, enable the cyberdeck and set user as secret and password as pass then head to localhost, go to settings and begin the registration process with a pong hub near you (enter the webapi address and your dero username). After registered, your products will be automatically submitted to and listed on the website / hub marketplace (if there is inventory and status is set to true/active).
  </p>
Currently 4 products types supported.<br>
<ol>
   <li>Physical Goods: Requires a UUID to be generated and sent to the customer in the out message for shipping address submission.</li>
   <li>Digital Goods: Provide a link to redeem the purchase or generate a UUID to append to the end of the link and optionally send the same UUID through the web api.</li>
   <li>Token Transfer: Requires a smart contract id for the token to be sent on purchase. Scids can be set at the product level and are inherited if Integrated Address level scids aren't supplied. If the token transfer fails it will send a refund to the buyer.</li>
   <li>Smart Contract Ownership Transfer: Will run the TransferOwnership function using the buyer address in as the input for a "newowner" sc variable and send the scid as a comment. Ensure that you use inventory of one! Sometimes the ownership change is reflected in gnomen, not sure of the specifics there yet.</li>
</ol>
<p>
The Pong Server inventory will check the product level inventory if specified will de-increment that first. If product level inventory is at 0 then it will see if there are Integrated Address level inventories and use those instead. If no inventory is found, a refund is issued. All inventory is updated immediately when a new transaction is detected and then sent to the website. Insufficient token fails set the incoming record as a failed transaction and sets status to false after the confirmation and updates the website (maybe could be done sooner).</p>
<p>
The refund rules... If there are no matching integrated addresses for the incoming transaction, or the status is set to false, the inventory is at 0 or the token transfer fails, a full refund for the order amount is issued automatically.
</p>
<p>
You can resubmit / update your shipping address for any order uuid / crc32 (hopefully before the order is shipped if needed). Physical goods orders found in the same block will be rolled into one response with the uuid for entering shipping information.
</p>
<p>
Registering with the pong hub is a one time affair where upon registration a unique id is assigned to the user and used thereforth for all later product submissions / updates. You can update / remove your Dero user name at any time but need to use the same wallet address. User names are now checked before submitting them to the server. If the username doesn't point to the wallet then the username field is set to blank and only the raw address will be used.
</p>
<p>
After registering, the pong server will send a put message to the pong hub every 5 minutes to maintain its listings. If a half hour goes by without a check-in then that seller's products will be removed from the storefront.
</p>
<p>
Now the Pong Server will use the starting balance and block and to keep a running total so that if there is a missed transaction the seller will be notified that there is a missing transaction and that they may need to find a full node and re-install the wallet if the missing transaction isn't restored. 
</p>
<br>
<p>
Roadmap: Tidy up some user experience things like adding loading indicators and more safeguards. Add more features to the hub portion like reviews, search, categories, sorting / ranking algorithm and locations.
</p>
<p>
Feel free to suggest, contribute, report bugs and all that good open source stuff!
</p>
<hr>
There are some other versions of Pong Servers available for running indenpendent of this project.<br>
The Pong Server Example from the Dero Project Written in GoLang by Captain Dero: https://github.com/deroproject/derohe/blob/main/cmd/rpc_examples/pong_server/pong_server.go<br>
The Pong Server Port to DASH by SecretNameBasis: https://github.com/secretnamebasis/dero-pong-server/blob/main/dero-pong-server
