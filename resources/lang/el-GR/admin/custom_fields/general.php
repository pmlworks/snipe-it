<?php

return [
    'custom_fields'		        => 'Προσαρμοσμένα πεδία',
    'manage'                    => 'Διαχείριση',
    'field'		                => 'Πεδίο',
    'about_fieldsets_title'		=> 'Σχετικά με τα σύνολα πεδίων',
    'about_fieldsets_text'		=> 'Τα Fieldsets σας επιτρέπουν να δημιουργήσετε ομάδες προσαρμοσμένων πεδίων που συχνά επαναχρησιμοποιούνται για συγκεκριμένους τύπους μοντέλου στοιχείων ενεργητικού.',
    'custom_format'             => 'Προσαρμοσμένη μορφή Regex...',
    'encrypt_field'      	        => 'Κρυπτογράφηση της αξίας του πεδίου στη βάση δεδομένων',
    'encrypt_field_help'      => 'Προειδοποίηση: H κρυπτογράφηση ενός πεδίου την καθιστά ανεξερεύνητη.',
    'encrypted'      	        => 'Κρυπτογραφημένο',
    'fieldset'      	        => 'Σύνολο πεδίων',
    'qty_fields'      	      => 'Πεδία Ποσ',
    'fieldsets'      	        => 'Σύνολο πεδίων',
    'fieldset_name'           => 'Ονομασία Συνόλου Πεδίων',
    'field_name'              => 'Όνομα πεδίου',
    'field_values'            => 'Τιμές πεδίου',
    'field_values_help'       => 'Προσθέστε επιλογές επιλογής, μία ανά γραμμή. Οι κενές γραμμές εκτός από την πρώτη γραμμή θα αγνοηθούν.',
    'field_element'           => 'Φόρμα στοιχείων',
    'field_element_short'     => 'Στοιχείο',
    'field_format'            => 'Τύπος',
    'field_custom_format'     => 'Προσαρμοσμένος τύπος',
    'field_custom_format_help'     => 'Το πεδίο επιτρέπει την χρήση εκφράσεων regex για επικύρωση. Πρέπει να ξεκινάει με "regex:" - για παράδειγμα, για την επικύρωση ενός προσαρμοσμένου πεδίου IMEI (15 αριθμητικά ψηφία), θα ήταν <code>regex:/^[0-9]{15}$/</code>.',
    'required'   		          => 'Απαιτείται',
    'req'   		              => 'Req.',
    'used_by_models'   		    => 'Χρησιμοποιήθηκε από τα μοντέλα',
    'order'   		            => 'Σειρά',
    'create_fieldset'         => 'Νέο σύνολο πεδίων',
    'update_fieldset'         => 'Ενημέρωση Συνόλου Πεδίων',
    'fieldset_does_not_exist'   => 'Το πεδίο :id δεν υπάρχει',
    'fieldset_updated'         => 'Το σύνολο πεδίων ενημερώθηκε',
    'create_fieldset_title' => 'Δημιουργία νέου συνόλου πεδίων',
    'create_field'            => 'Νέο προσαρμοσμένο πεδίο',
    'create_field_title' => 'Δημιουργία νέου προσαρμοσμένου πεδίου',
    'value_encrypted'      	        => 'Η τιμή αυτού του πεδίου είναι κρυπτογραφημένη στη βάση δεδομένων. Μόνο οι διαχειριστές θα μπορούν να δουν την αποκρυπτογραφημένη τιμή',
    'show_in_email'     => 'Να περιλαμβάνεται η τιμή αυτού του πεδίου στα emails χρέωσης ου αποστέλονται στους χρήστες; Κρυπτογραφημένα πεδία δεν μπορούν να περιληφθούν σε emails',
    'show_in_email_short' => 'Συμπερίληψη στα email',
    'help_text' => 'Κείμενο Βοήθειας',
    'help_text_description' => 'Αυτό είναι προαιρετικό κείμενο που θα εμφανίζεται κάτω από τα στοιχεία της φόρμας κατά την επεξεργασία ενός περιουσιακού στοιχείου για να παρέχει το πλαίσιο στο πεδίο.',
    'about_custom_fields_title' => 'Σχετικά Με Προσαρμοσμένα Πεδία',
    'about_custom_fields_text' => 'Τα προσαρμοσμένα πεδία σας επιτρέπουν να προσθέσετε αυθαίρετα χαρακτηριστικά στα περιουσιακά στοιχεία.',
    'add_field_to_fieldset' => 'Προσθήκη πεδίου στο σύνολο πεδίων',
    'make_optional' => 'Απαιτείται - κάντε κλικ για να κάνετε προαιρετικό',
    'make_required' => 'Προαιρετικό - κάντε κλικ για να κάνετε την απαιτούμενη',
    'reorder' => 'Αναδιάταξη',
    'db_field' => 'Πεδίο Βάσης Δεδομένων',
    'db_convert_warning' => 'ΠΡΟΕΙΔΟΠΟΙΗΣΗ. Αυτό το πεδίο είναι στον προσαρμοσμένο πίνακα πεδίων ως <code>:db_column</code> αλλά θα πρέπει να είναι <code>:expected</code>.',
    'is_unique' => 'Η αξία αυτή πρέπει να είναι μοναδική σε όλα τα περιουσιακά στοιχεία',
    'unique' => 'Μοναδικό',
    'display_in_user_view' => 'Επιτρέψτε στον συνδεδεμένο χρήστη να δει αυτές τις τιμές στη σελίδα προβολής των περιουσιακών στοιχείων',
    'display_in_user_view_table' => 'Ορατό στο χρήστη',
    'auto_add_to_fieldsets' => 'Αυτόματη προσθήκη σε κάθε νέο σύνολο πεδίων',
    'add_to_preexisting_fieldsets' => 'Προσθήκη σε οποιαδήποτε υπάρχουσα fieldsets',
    'show_in_listview' => 'Εμφάνιση στις προβολές λίστας από προεπιλογή. Οι εξουσιοδοτημένοι χρήστες θα μπορούν ακόμα να εμφανίζουν/αποκρύπτουν μέσω του επιλογέα στήλης',
    'show_in_listview_short' => 'Εμφάνιση στις λίστες',
    'show_in_requestable_list_short' => 'Εμφάνιση στη λίστα απαιτούμενων στοιχείων',
    'show_in_requestable_list' => 'Εμφάνιση τιμής στη λίστα Απαιτούμενων στοιχείων. Κρυπτογραφημένα πεδία δεν θα εμφανίζονται',
    'encrypted_options' => 'Αυτό το πεδίο είναι κρυπτογραφημένο, οπότε ορισμένες επιλογές εμφάνισης δεν θα είναι διαθέσιμες.',
    'display_checkin' => 'Display in checkin forms',
    'display_checkout' => 'Display in checkout forms',
    'display_audit' => 'Display in audit forms',
    'types' => [
        'text' => 'Text Box',
        'listbox' => 'List Box',
        'textarea' => 'Textarea (multi-line)',
        'checkbox' => 'Checkbox',
        'radio' => 'Radio Buttons',
    ],
];
