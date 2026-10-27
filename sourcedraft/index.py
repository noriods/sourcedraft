"""SQLite FTS5 index per project."""
import re
import sqlite3


def build(db, chunks):
    con = sqlite3.connect(db)
    con.execute("DROP TABLE IF EXISTS chunks")
    con.execute("CREATE VIRTUAL TABLE chunks USING fts5(id UNINDEXED, file UNINDEXED, text)")
    con.executemany("INSERT INTO chunks VALUES (?,?,?)", [(c["id"], c["file"], c["text"]) for c in chunks])
    con.commit()
    con.close()


# ponytail: keyword (BM25) search only, swap this one function for embeddings if recall is poor
def search(db, query, k=8):
    words = re.findall(r"\w+", query)
    if not words:
        return []
    con = sqlite3.connect(db)
    rows = con.execute(
        "SELECT id, file, text FROM chunks WHERE chunks MATCH ? ORDER BY bm25(chunks) LIMIT ?",
        (" OR ".join(f'"{w}"' for w in words), k),
    ).fetchall()
    con.close()
    return [{"id": r[0], "file": r[1], "text": r[2]} for r in rows]
