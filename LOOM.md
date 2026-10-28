# Loom talk track (about 2 minutes), recorded in the web screen

Start the screen (see "Start it" below), sign in, open the project. Have one finished draft ready so you do not wait for the model (a draft takes a few minutes).

**0:00, Projects, then demo-management**
"This turns your own course documents into editable slides and case studies. Every point shows where it came from. One project per course or client. These are the documents: an open textbook chapter, publisher-style slides and the learning objectives."

**0:20, point at Ingest and the Draft box (do not click)**
"Ingest reads the files and cuts them into small numbered pieces. Then I pick what to make, here a teaching deck, and a topic. It writes in the background and the page updates by itself."

**0:40, open a draft from "Drafts to review"**
"Review page. The slide on the left. On the right the student text, then the instructor notes. The green bar: the citation check passed, so every point names a source that exists in your documents."

**1:05, click one source**
"Click a source and you see the exact passage it came from. If the model cites something that is not in your documents, the run fails. Nothing is invented."

**1:25, scroll to Approve, click Approve with the name empty**
"Nothing is final yet. It is blocked without a name. A person has to approve."

**1:40, type a name, tick the box, Approve, then open the final deck download**
"Approved: the clean files appear, with no DRAFT mark, and the page records who and when. The final deck opens in PowerPoint, instructor notes in the speaker notes, a source line on every slide."

**1:55, close**
"For private material the model can run on your own machine or your own cloud. Another course or your own template is a new folder and one settings file. I can run this on one of your own chapters this week."

## Start it

```bash
cd ~/work/sourcedraft/ui
php artisan serve --host=127.0.0.1 --port=8800 &
php artisan queue:work &
```

Open http://127.0.0.1:8800 and sign in with `demo@sourcedraft.test` / `sourcedraft`.
