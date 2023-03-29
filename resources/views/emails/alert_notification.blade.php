<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="format-detection" content="telephone=no" /> <!-- disable auto telephone linking in iOS -->
        <title>Customer Dashboard</title>
        <link href="https://fonts.googleapis.com/css?family=Roboto+Condensed:400,700" rel="stylesheet">
        
    </head>
    <body bgcolor="#fafafa" leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">

        <!-- CENTER THE EMAIL // -->
        <!--
            1.  The center tag should normally put all the
                content in the middle of the email page.
                I added "table-layout: fixed;" style to force
                yahoomail which by default put the content left.

            2.  For hotmail and yahoomail, the contents of
                the email starts from this center, so we try to
                apply necessary styling e.g. background-color.
        -->
        <center style="background-color:#fafafa;">
        	<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable" style="table-layout: fixed;max-width:100% !important;width: 100% !important;min-width: 100% !important;">
            	<tr>
                	<td align="center" valign="top" id="bodyCell">
                        <!-- // EMAIL HEADER -->

                        <!-- EMAIL HEADER // -->
                        <!--
                            The table "emailBody" is the email's container.
                            Its width can be set to 100% for a color band
                            that spans the width of the page.
                        -->
                        <table bgcolor="#fafafa" border="0" cellpadding="0" cellspacing="0" width="600" id="emailHeader">

                            <!-- HEADER ROW // -->
                            <tr>
                                <td align="center" valign="top">
                                    <!-- CENTERING TABLE // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                        <tr>
                                            <td align="center" valign="top">
                                                <!-- FLEXIBLE CONTAINER // -->
                                                <table border="0" cellpadding="10" cellspacing="0" width="600" class="flexibleContainer">
                                                    <tr>
                                                        <td valign="top" width="600" class="flexibleContainerCell">

                                                            <!-- CONTENT TABLE // -->
                                                            <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%">
                                                                <tr>
                                                                    <!--
                                                                        The "invisibleIntroduction" is the text used for short preview
                                                                        of the email before the user opens it (50 characters max). Sometimes,
                                                                        you do not want to show this message depending on your design but this
                                                                        text is highly recommended.

                                                                        You do not have to worry if it is hidden, the next <td> will automatically
                                                                        center and apply to the width 100% and also shrink to 50% if the first <td>
                                                                        is visible.
                                                                    -->
                                                                    <td align="left" valign="middle" id="invisibleIntroduction" class="flexibleContainerBox" style="display:none !important;">
                                                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:100%;">
                                                                            <tr>
                                                                                <td align="left" class="textContent">
                                                                                    <div style="font-family:'Roboto Condensed', Helvetica,Arial,sans-serif;font-size:13px;color:#828282;text-align:center;line-height:120%;">
                                                                                        The introduction of your message preview goes here. Try to make it short.
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td align="right" valign="middle" class="flexibleContainerBox">
                                                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:100%;">
                                                                            <tr>
                                                                                <td align="left" class="textContent">
                                                                                    <!-- CONTENT // -->
                                                                                    <div style="font-family:'Roboto Condensed', Helvetica,Arial,sans-serif;font-size:13px;color:#828282;text-align:center;line-height:120%;">
                                                                                        Astra FMS Customer Dashboard
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <!-- // FLEXIBLE CONTAINER -->
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- // CENTERING TABLE -->
                                </td>
                            </tr>
                            <!-- // END -->

                        </table>
                        <!-- // END -->


                    	<!-- EMAIL CONTAINER // -->
                        <!--
                        	The table "emailBody" is the email's container.
                            Its width can be set to 100% for a color band
                            that spans the width of the page.
                        -->

                        <table 
                            bgcolor="#FFFFFF"  
                            border="0" 
                            cellpadding="0" 
                            cellspacing="0" 
                            width="600" 
                            id="emailBody" 
                            style="-ms-box-shadow: rgba(147, 147, 147, 0.1) 0 1px 15px 1px;
                            -o-box-shadow: rgba(147, 147, 147, 0.1) 0 1px 15px 1px;
                            box-shadow: rgba(147, 147, 147, 0.1) 0 1px 15px 1px;
                            border:solid 1px #efefef;"
                        >


							<!-- MODULE ROW // -->
                            <!--
                            	To move or duplicate any of the design patterns
                                in this email, simply move or copy the entire
                                MODULE ROW section for each content block.
                            -->
							<tr>
                            	<td align="center" valign="top">
                                    <div style="background: #f78731; height:5px;"></div>
                                </td>
                            </tr>
                            <!-- // MODULE ROW -->


							<!-- MODULE ROW // -->
                            <!-- The "mc:hideable" is a feature for MailChimp which allows
                                  you disable certain row. It works perfectly for structure.
                                  http://kb.mailchimp.com/article/template-language-creating-editable-content-areas/
                            -->
							<tr mc:hideable>
                            	<td align="center" valign="top">
                                	<!-- CENTERING TABLE // -->
                                	<table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    	<tr>
                                        	<td align="center" valign="top">
                                            	<!-- FLEXIBLE CONTAINER // -->
                                            	<table border="0" cellpadding="30" cellspacing="0" width="600" class="flexibleContainer">
                                                	<tr>
                                                    	<td valign="top" width="600" class="flexibleContainerCell">

                                                            <!-- CONTENT TABLE // -->
                                                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                                <tr>
                                                                    <td valign="top" class="textContent">
                                                                        <!-- <div style="margin-bottom:30px"><img src="brand.png" width="75" alt=""/></div> -->
                                                                        <h3 style="color:#3f3f3f;line-height:125%;font-family:'Roboto Condensed', Helvetica,Arial,sans-serif;font-size:20px;font-weight:normal;margin-top:0;margin-bottom:20px;text-align:left;">Alert Notification From Astra FMS Customer Dashboard</h3>
                                                                        <div style="text-align:left;font-family:'Roboto Condensed', Helvetica,Arial,sans-serif;font-size:16px;margin-bottom:0;color:#6f6f6f;line-height:135%;">
                                                                            <p>Alert for <?php echo $fleet_group_name ?></p>
                                                                            <p>Vehicle <?php echo $license_plate ?> was <?php echo $alert ?> on <?php echo $date ?></p>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                            <!-- // CONTENT TABLE -->

                                                        </td>
                                                    </tr>
                                                </table>
                                                <!-- // FLEXIBLE CONTAINER -->
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- // CENTERING TABLE -->
                                </td>
                            </tr>
                            <!-- // MODULE ROW -->

                            <!-- MODULE ROW // -->
                            <tr>
                                <td align="center" valign="top">
                                    <!-- CENTERING TABLE // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background:#f0f0f0;">
                                        <tr>
                                            <td align="center" valign="top">
                                                <!-- FLEXIBLE CONTAINER // -->
                                                <table border="0" cellpadding="30" cellspacing="0" width="600" class="flexibleContainer">
                                                    <tr>
                                                        <td style="padding-bottom:0;" valign="top" width="600" class="flexibleContainerCell">

                                                            <!-- CONTENT TABLE // -->
                                                            <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:30px">
                                                                <tr>
                                                                    <td align="left" valign="top" class="flexibleContainerBox">
                                                                        <table border="0" cellpadding="0" cellspacing="0" width="40" style="max-width:100%;">
                                                                            <tr>
                                                                                <td align="left" class="textContent">
                                                                                    <!-- <img src="warning.png" width="30" class="flexibleImageSmall" style="max-width:100%;" alt="Text" title="Text" /> -->
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td align="right" valign="middle" class="flexibleContainerBox">
                                                                        <table class="flexibleContainerBoxNext" border="0" cellpadding="0" cellspacing="0" width="490" style="max-width:100%;">
                                                                            <tr>
                                                                                <td align="left" class="textContent">
                                                                                    <div style="text-align:left;font-family:'Roboto Condensed', Helvetica,Arial,sans-serif;font-size:16px;margin-bottom:0;color:#6f6f6f;line-height:135%;">
                                                                                        &nbsp;
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                            <!-- // CONTENT TABLE -->

                                                        </td>
                                                    </tr>
                                                </table>
                                                <!-- // FLEXIBLE CONTAINER -->
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- // CENTERING TABLE -->
                                </td>
                            </tr>
                            <!-- // MODULE ROW -->


                            <!-- MODULE ROW // -->
                            <tr>
                                <td align="center" valign="top">
                                    <!-- CENTERING TABLE // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                        <tr>
                                            <td align="center" valign="top">
                                                <!-- FLEXIBLE CONTAINER // -->
                                                <table border="0" cellpadding="0" cellspacing="0" width="600" class="flexibleContainer">
                                                    <tr>
                                                        <td align="center" valign="top" width="600" class="flexibleContainerCell">
                                                            <table border="0" cellpadding="30" cellspacing="0" width="100%">
                                                                <tr>
                                                                    <td align="center" valign="top">

                                                                        <!-- CONTENT TABLE // -->
                                                                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                                            <tr>
                                                                                <td valign="top" class="textContent">
                                                                                    <div style="margin-bottom:0;margin-top:3px;margin-left:auto;margin-right:auto; display:table;">
                                                                                        <a href="#" style="display:bock; width:40px; height:40px; float:left; margin: 0 5px;">
                                                                                            <img src="https://seramonicaproductionsa.blob.core.windows.net/monica-resources/images/facebook.png" alt="" style="border-radius:50%;  width:40px; border:solid 2px #efefef; -ms-box-shadow: rgba(147, 147, 147, 0.1) 0 1px 15px 1px;-o-box-shadow: rgba(147, 147, 147, 0.1) 0 1px 15px 1px;box-shadow: rgba(147, 147, 147, 0.1) 0 1px 15px 1px;"/>
                                                                                        </a>
                                                                                        <a href="#" style="display:bock; width:40px; height:40px; float:left; margin: 0 5px;">
                                                                                            <img src="https://seramonicaproductionsa.blob.core.windows.net/monica-resources/images/twitter.png" alt="" style="border-radius:50%;  width:40px; border:solid 2px #efefef; -ms-box-shadow: rgba(147, 147, 147, 0.1) 0 1px 15px 1px;-o-box-shadow: rgba(147, 147, 147, 0.1) 0 1px 15px 1px;box-shadow: rgba(147, 147, 147, 0.1) 0 1px 15px 1px;"/>
                                                                                        </a>
                                                                                        <a href="#" style="display:bock; width:40px; height:40px; float:left; margin: 0 5px;">
                                                                                            <img src="https://seramonicaproductionsa.blob.core.windows.net/monica-resources/images/google.png" alt="" style="border-radius:50%;  width:40px; border:solid 2px #efefef; -ms-box-shadow: rgba(147, 147, 147, 0.1) 0 1px 15px 1px;-o-box-shadow: rgba(147, 147, 147, 0.1) 0 1px 15px 1px;box-shadow: rgba(147, 147, 147, 0.1) 0 1px 15px 1px;"/>
                                                                                        </a>
                                                                                        <a href="#" style="display:bock; width:40px; height:40px; float:left; margin: 0 5px;">
                                                                                             <img src="https://seramonicaproductionsa.blob.core.windows.net/monica-resources/images/instagram.png" alt="" style="border-radius:50%;  width:40px; border:solid 2px #efefef; -ms-box-shadow: rgba(147, 147, 147, 0.1) 0 1px 15px 1px;-o-box-shadow: rgba(147, 147, 147, 0.1) 0 1px 15px 1px;box-shadow: rgba(147, 147, 147, 0.1) 0 1px 15px 1px;"/>
                                                                                        </a>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td valign="top" class="textContent">
                                                                                    <div style="margin-bottom:0;margin-top:30px;margin-left:auto;margin-right:auto; display:table;">
                                                                                        <a href="#" style="display:bock;margin:0 5px;"><img src="https://seramonicaproductionsa.blob.core.windows.net/monica-resources/images/ios.png" alt="" width="150"/></a>
                                                                                        <a href="#" style="display:bock;margin:0 5px;"><img src="https://seramonicaproductionsa.blob.core.windows.net/monica-resources/images/android.png" alt="" width="150"/></a>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                        <!-- // CONTENT TABLE -->

                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <!-- // FLEXIBLE CONTAINER -->
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- // CENTERING TABLE -->
                                </td>
                            </tr>
                            <!-- // MODULE ROW -->

                        </table>
                        <!-- // END -->

                        <!-- EMAIL FOOTER // -->
                        <!--
                            The table "emailBody" is the email's container.
                            Its width can be set to 100% for a color band
                            that spans the width of the page.
                        -->
                        <table bgcolor="#fafafa" border="0" cellpadding="0" cellspacing="0" width="600" id="emailFooter">

                            <!-- FOOTER ROW // -->
                            <!--
                                To move or duplicate any of the design patterns
                                in this email, simply move or copy the entire
                                MODULE ROW section for each content block.
                            -->
                            <tr>
                                <td align="center" valign="top">
                                    <!-- CENTERING TABLE // -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                        <tr>
                                            <td align="center" valign="top">
                                                <!-- FLEXIBLE CONTAINER // -->
                                                <table border="0" cellpadding="0" cellspacing="0" width="600" class="flexibleContainer">
                                                    <tr>
                                                        <td align="center" valign="top" width="600" class="flexibleContainerCell">
                                                            <table border="0" cellpadding="30" cellspacing="0" width="100%">
                                                                <tr>
                                                                    <td valign="top" bgcolor="#fafafa">

                                                                        <div style="font-family:'Roboto Condensed', Helvetica,Arial,sans-serif;font-size:13px;color:#9f9f9f;text-align:center;line-height:120%;">Copyright © 2019 Astra FMS Customer Dashboard. All rights reserved</div>
                                                                        <div style="font-family:'Roboto Condensed', Helvetica,Arial,sans-serif;font-size:13px;color:#9f9f9f;text-align:center;line-height:120%;">Sunter, Jakarta Utara</div>

                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <!-- // FLEXIBLE CONTAINER -->
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- // CENTERING TABLE -->
                                </td>
                            </tr>

                        </table>
                        <!-- // END -->

                    </td>
                </tr>
            </table>
        </center>
    </body>
</html>
