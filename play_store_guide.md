# Play Store Upload Instructions

To upload your new Hybrid WebView App to the Google Play Store, follow these steps:

## Prerequisites
1. **Google Play Console Developer Account:** You must have a registered account (costs a one-time fee of $25).
2. **App Assets:**
   - App Icon (512x512 pixels, PNG or JPEG)
   - Feature Graphic (1024x500 pixels, PNG or JPEG)
   - Phone Screenshots (2-8 screenshots)
3. **Legal Documents (Included above):**
   - You need a live URL for your **Privacy Policy** and **Terms and Conditions**.
   - *Action Required:* Upload the `privacy-policy.html` and `terms.html` files I generated to your web server (e.g., `https://sanghasthan.yuktaa.com/privacy-policy.html`).

## Step-by-Step Upload Guide

### 1. Create App in Play Console
1. Go to the [Google Play Console](https://play.google.com/console/).
2. Click **Create app**.
3. Fill in the App Name (e.g., "संघस्थान"), Default language, App or Game (App), Free or Paid (Free).
4. Accept Developer Program Policies and US export laws.
5. Click **Create app**.

### 2. Set Up Your App (Dashboard)
On the app dashboard, follow the "Set up your app" tasks:
1.  **Set privacy policy:** Paste the URL where you hosted the `privacy-policy.html` file.
2.  **App access:** Since users need to log in to see data, select "All or some functionality is restricted" and provide a set of test login credentials (Username/Password) so Google reviewers can test the app.
3.  **Ads:** Select whether your app has ads (likely "No").
4.  **Content rating:** Fill out the questionnaire to get an age rating. It will likely be Rated for 3+ since it's an organizational tool.
5.  **Target audience:** Select the target age groups (e.g., 18 and over).
6.  **News apps:** Select "No".
7.  **Data safety:**
    - Does your app collect or share any of the required user data types? **Yes** (You collect Names, Phone Numbers, Login Info through the web view).
    - Is all of the user data collected by your app encrypted in transit? **Yes** (Since your website uses HTTPS).
    - Do you provide a way for users to request that their data is deleted? **Yes** (Users can ask their Mukhya Shikshak).
    - **Data Types to declare:** Personal Info (Name, Phone number). Purpose: App functionality, Account management.
8.  **Government apps:** Select "No" (Assuming this is an independent NGO/organization, not a government entity).
9.  **Store settings:** Select Category (e.g., Productivity or Social) and provide your contact email.
10. **Store listing:** Upload your app name, short description, full description, App icon, feature graphic, and screenshots.

### 3. Generate a Signed App Bundle (AAB)
*Note:* The Play Store currently requires `.aab` (Android App Bundle) files for new apps, not `.apk` files.
1. Google requires a signed release. I have currently built an APK for manual install testing.
2. If you are ready for the final Play Store upload, let me know and I will run the command to build the `.aab` file: `flutter build appbundle`. (It requires setting up a keystore, which we can do together).

### 4. Release Your App
1. On the left menu, go to **Testing > Closed testing** or **Production**.
2. Click **Create new release**.
3. Upload the `.aab` file generated in the previous step.
4. Add release notes.
5. Click **Save**, then **Review release**.
6. Finally, click **Start rollout**.

Google will review the app (can take 1-7 days). Once approved, it will be live on the Play Store!
