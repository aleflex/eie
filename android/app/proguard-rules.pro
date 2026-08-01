# Proguard rules for EIE Android App
# Add project specific ProGuard rules here.
# You can control the set of keep rules by using attributes on class, method, and fields.
-keepattributes Signature, InnerClasses, EnclosingMethod
-keepclassmembers class * {
    @com.google.gson.annotations.SerializedName <fields>;
}
-keep class com.eie.gestion.data.model.** { *; }
