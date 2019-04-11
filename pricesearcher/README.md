# web-developer-code-test

See it in action: http://www.everygamegoing.com/pricesearcher/public_html/pricesearcher/actionItem/

Please see notes at the bottom of this file for my thoughts in relation to this exercise.



## Web Developer Role - Code Test

Thank you for applying to Pricesearcher!

As part of the application process we would like you to complete a short technical challenge
to assist in our evaluation. Your solution will be discussed further at the interview stage,
along with any other thoughts you have about this exercise.

## Task

### Description

The objective is to build a simple web tool that:
1. Presents a set of open Action Items to the user,
2. Allows the user to pick one and "complete" it by uploading a file from their computer,
3. Marks that Action Item as "done" and stores the corresponding file provided.

The Action Items should be seeded from the [given data file](./test_data.json), and
stored on the server side somehow, represented by the given id number and text, as well as
their status and the attached file (if the Action Item is done).

It should be possible in principle to retrieve the files stored against the corresponding items,
but the UI to do so is out of scope of this exercise.


### Implementation

You are free to implement this using whatever frameworks, technologies and programming languages
you feel appropriate. There is no time limit on how long you can spend on the task, but have in
mind that we evaluate the answers in a FCFS order.

The objectives of the exercise are for you to:
- Demonstrate good choices of frameworks, languages and technologies in regard to:
  - suitability to the given task
  - avoiding large amounts of unnecessary programming
  - the technologies we already use, i.e. the skills we are looking for in the job advert
- Demonstrate the ability to use the chosen frameworks, languages and technologies effectively,
  including configuration and test writing
- Demonstrate awareness of the kinds of issues that arise using such technologies for such an
  application - write them in the README.md file for discussion during the interview.

During the interview at the next stage, we will discuss about the overall quality of your solution
as well as specific aspects as:
- Simplicity,
- Readability,
- Scalability,
- Maintainability


### Submission

Your work should be submitted as a Git repository. If hosted publicly, please don't mention the
interview in the repo or code, unless you feel comfortable making it public you're interviewing
with us.

Include any notes, comments, and known issues and considerations in a README.md file.

In particular, you should document how to **run your submission** and if it relies on any external
dependencies, what those are and how to set them up.

Good luck! Have fun!




### Notes

1. The ticket is abstract; a real ticket should be in the form of:

I WANT: a list view of Action Items with an option to Upload a file to complete the items
SO THAT: [business reason behind the request, to see if it is part of business priority at current
moment]
ACCEPTANCE CRITERIA:
1. Can I visit the following URL actionItem/ and see a list of the Action Items that are currently Open?
2. If an Action Item is open, can I see a button 'Upload File'?
3. If I click that button, does a modal appear instructing me to find the file and attach it?
4. Can I click out of the modal if I change my mind?
5. If a file is attached, does the page refresh with a confirmation message?

I would then say:

1. Can I visit the following URL actionItem/ and see a list of the Action Items that are currently Open? - YES
2. If an Action Item is open, can I see a button 'Upload File'? - YES
3. If I click that button, does a modal appear instructing me to find the file and attach it? - YES
4. Can I click out of the modal if I change my mind? - YES
5. If a file is attached, does the page refresh with a confirmation message? - YES

2. The business reason, if given, might suggest further tickets. Some I could envisage are:

Add A Filter To The Action Items So I Can See All Action Items In A Particular State
Add A Thumbnail Image Of Completed Action Items So I Can See At A Glance
Add A Details View Of Each Action Item So I Can See All Data Regarding That Action Item
Allow An Action Item's State To Be Changed
Allow An Action Item To Be Updated
Ensure All Action Item Interaction Is Logged So That All Users Who Edited An Action Item Can Be Traced


3. As this appears to be an internal ticket (Admin Panel), you can do the bulk of the testing within the
code itself (during execution) - if a file does not upload correctly, the site will not go down and
income from customers will likely not be affected. So you can speed up development time by just being
practical in the code, doing the necessary checks and balances and functionally testing it at the end.

4. I have written the code for scaleability, introducing state_ids and state_descs in line with how 
these would be stored in a MySQL database.

5. More junior developers would probably redirect the user to a form to upload the file rather than do
the modal. Frameworks and experience make introducing the modal easy and provide a smooth user experience.

6. Divide out the location of the data so that it is in a constant with class scope; makes the code scaleable.

7. Divide out the view logic from the controller logic.

8. Create a model with the states in rather than just use arbitrary values that a subsequent dev might not
realise the significance of. Model can then also be used in unit testing.

