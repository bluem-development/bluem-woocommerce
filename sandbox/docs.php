<?php 
// index.php


// S1212 = SenderID, 
// BrandID = NextDeliMandate

// ******* TEST ENV **********
// sendId: S1212
// accessToken: ef552fd4012f008a6fe3000000690107003559eed42f0000

// Create transaction (ePayment):
https://test.viamijnbank.net/pr/createTransactionWithToken?token=<accessToken> 

// Request transaction status (ePayment):
https://test.viamijnbank.net/pr/requestTransactionStatusWithToken?token=<accessToken> 

// Create transaction (eMandate):
https://test.viamijnbank.net/mr/createTransactionWithToken?token=<accessToken> 

// Request transaction status (eMandate):
https://test.viamijnbank.net/mr/requestTransactionStatusWithToken?token=<accessToken> 

// Create transaction (Identity):
https://test.viamijnbank.net/ir/createTransactionWithToken?token=<accessToken> 

// Request transaction status (Identity):
https://test.viamijnbank.net/ir/requestTransactionStatusWithToken?token=<accessToken> 

// Create transaction (IBANCheck):
https://test.viamijnbank.net/icr/createTransactionWithToken?token=<accessToken>



// every request: 
// ONLY POST
//  Set the following request headers for the Request (for details see 3.1):
	// x-ttrs-date
	// x-ttrs-files-count
	// x-ttrs-filename
// 3. Set the content of the Request with the following content headers (for details see 3.2):
	// content-type: application/xml;
	// type=TRX (or PTX, ITX, INX, SRX, PSX, ISX);
	// charset=utf-8;