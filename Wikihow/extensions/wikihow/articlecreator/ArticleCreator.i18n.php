<?php

$messages = array();

$messages['en'] = array (
	'articlecreator' => 'Article Creator',
	'ac-section-intro-name' => 'Introduction',
	'ac-section-intro-desc' => 'Enter a short introduction to quickly introduce the reader to your article.',
	'ac-section-intro-button-txt' => 'Add Introduction',
	'ac-section-intro-placeholder' => 'Enter your introduction here',
	'ac-section-steps-name' => 'Steps',
	'ac-section-steps-desc' => 'Enter each step of your article here. Press "Add Step" after each individual step. Be as detailed as possible when describing how to do each step.  It’s always better to over-describe a step than under-describe it.',
	'ac-section-steps-name-method-placeholder' => 'Enter the method or part title here.',
	'ac-section-steps-method-done-button-txt' => 'Save',
	'ac-section-steps-addstep-placeholder' => 'Enter one step here and then click "Add Step"',
	'ac-section-steps-button-txt' => 'Add Step',
	'ac-section-tips-name' => 'Tips',
	'ac-section-tips-desc' => 'Enter any useful tips here. Press "Add Tip" after each individual tip.',
	'ac-section-tips-button-txt' => 'Add Tip',
	'ac-section-tips-placeholder' => 'Enter one tip here and then click "Add Tip"',
	'ac-section-warnings-name' => 'Warnings',
	'ac-section-warnings-desc' => 'Enter any warnings here. Press "Add Warning" after each individual warning.',
	'ac-section-warnings-button-txt' => 'Add Warning',
	'ac-section-warnings-placeholder' => 'Enter one warning here and then click "Add Warning"',
	'ac-section-sources-name' => 'Sources and Citations',
	'ac-section-sources-desc' => 'Enter sources here. Press "Add Source" after each individual source.',
	'ac-section-sources-button-txt' => 'Add Source',
	'ac-section-sources-placeholder' => 'Enter one text or URL source (eg http://www.wikihow.com/) and then click "Add Source"',
	'ac-section-references-name' => 'References',
	'ac-section-references-desc' => 'Enter references here. Press "Add Reference" after each individual reference.',
	'ac-section-references-button-txt' => 'Add Reference',
	'ac-section-references-placeholder' => 'Enter one text or URL reference (eg http://www.wikihow.com/) and then click "Add Reference"',
	'ac-section-button-txt' => 'Add',	
	'ac-edit-summary' => 'Creating a new article via the Article Creator',
	'ac-formatting-warning-txt' => 'We have automatically formatted this step to match wikiHow formatting guidelines',
	'ac-formatting-warning-title' => 'Auto-formatting Step',
	'ac-invalid-edit-token'	=> 'You have an invalid edit token which means we can\'t go any further from here.',
	'ac-title-exists' => 'This article already exists.  Please edit the <a href="$1" target="_blank">existing article</a>.',
	'ac-cannot-create' => "You don't have permission to create this article",
	'ac-html-title' => 'Creating "How to $1" - wikiHow',
	'ac-successful-publish' => 'Congratulations! Your article has been published',
	'ac-copy-wikitext' => 'All is not lost!  Here is the wikitext generated from your article. You can copy this to save your  work.',
	'ac-validation-error-title' => 'Uh-oh',
	'ac-error-too-short' => 'Please provide a more detailed set of steps before publishing this article.',
	'ac-error-no-steps' => 'Please provide at least a few steps before publishing this article.',
	'ac-error-only-bullets' => 'This step only contains bullets. Please include a sentence before your bullet, and then add your step.',
	'ac-error-missing-method-names' => 'Please name each of your methods or parts before publishing this article.',
	'ac-error-blocked' => 'Your user account is blocked from saving this article.',
	'ac-error-spam' => 'The content of this article contains spam.',
	'ac-error-editfilter' => 'Problem running EditFilterMergedContent hook.',
	'ac-confirm-delete-step' => 'Are you sure you want to delete this step?',
	'ac-confirm-delete-bullet' => 'Are you sure you want to delete this bullet?',
	'ac-confirm-remove-method' => "<div class='ac_method_id'>$1</div>Delete the $2 <span class='ac_method_name'>$3</span>?",
	'ac-confirm-discard-article' => 'Do you really want to discard your article?  All changes will be lost.',
	'ac-confirm-advanced-editor' => "Do you really want to switch to the advanced editor?  All changes will be lost and you will not be able to later return to this editor.",
	'ac-question-neither' => 'Choose "Neither" if you are only writing about one way to do your how-to. If this option is disabled, it is because you already have multiple methods or parts in your article. You will need to remove them before you select "Neither".',
	'ac-question-neither-title' => 'Neither',
	'ac-question-methods' => 'Choose "Multiple Methods" if you want to give the reader multiple options for how to complete the article. The reader should be able to use any one of your methods independently to complete their task.',
	'ac-question-methods-title' => 'Multiple Methods',
	'ac-question-parts' => 'Choose "Multiple Parts" when the reader needs to complete more than one part to complete their task (such as "Preparation", "Creation", and "Completion").',
	'ac-question-parts-title' => 'Multiple Parts',
	'ac-method-selector-txt' => 'Do you have another method for ',
	'ac-part-selector-txt' => 'Do you have another part for ',
	'ac-add-method-button-txt' => 'Add a Method',
	'ac-add-part-button-txt' => 'Add a Part',
	'ac-modal-head' => 'Congratulations, your article is published!',
	'ac-modal-step1' => 'Article Created!',
	'ac-modal-step2' => 'Article in Review',
	'ac-modal-step3' => 'Viewable by millions!',
	'ac-modal-email-error' => "Whoops! If you'd like to receive email notifications, please enter a valid email.",
	'ac-modal-email-hdr' => 'Email me about updates to my article',
	'ac-modal-email-msg' => "Thanks for starting a new article! Like all new articles, it's now waiting for quality review.",
	'ac-modal-email-ph' => 'your email address',
	'ac-modal-email2-ph' => 'Enter email addresses here',
	'ac-modal-email2-msg' => 'You can enter multiple emails separated by commas',
	'ac-modal-shareit-hdr' => 'Share your new article with the world!',
	'ac-modal-share' => 'Share',
	'ac-modal-email' => 'Email',
	'ac-modal-view-article-btn' => 'Done, go to article',
	'ac-modal-checkbox' => "Don't show this again",
	'ac-modal-info-tip' => "We'll send an email when this article gets updated or reaches important milestones.",
	'ac-modal-sign-up' => 'Create an account',
	'ac-modal-anon-msg' => "Want to see updates on your article's progress and get personal credit for what you just wrote? Create a wikiHow account and we'll link your new article to it.",
	'ac-overwrite-reason' => 'Automatically deleting demoted article to make way for a rewrite',
);
