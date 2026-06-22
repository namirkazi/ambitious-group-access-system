from flask import Flask, request, jsonify
import os
import json
import numpy as np

# These imports will work once packages install
import face_recognition

app = Flask(__name__)

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
KNOWN_DIR = os.path.join(BASE_DIR, "known_faces")
UPLOAD_DIR = os.path.join(BASE_DIR, "uploads")

os.makedirs(KNOWN_DIR, exist_ok=True)
os.makedirs(UPLOAD_DIR, exist_ok=True)


@app.route("/")
def home():
    return "Face service online"


@app.route("/register-face", methods=["POST"])
def register_face():

    visitor_id = request.form.get("visitor_id")

    if not visitor_id:
        return jsonify({
            "success": False,
            "message": "visitor_id missing"
        })

    if "image" not in request.files:
        return jsonify({
            "success": False,
            "message": "image missing"
        })

    image = request.files["image"]

    temp_path = os.path.join(
        UPLOAD_DIR,
        f"register_{visitor_id}.jpg"
    )

    image.save(temp_path)

    try:

        img = face_recognition.load_image_file(temp_path)

        encodings = face_recognition.face_encodings(img)

        if len(encodings) == 0:
            return jsonify({
                "success": False,
                "message": "No face detected"
            })

        if len(encodings) > 1:
            return jsonify({
                "success": False,
                "message": "Multiple faces detected"
            })

        encoding = encodings[0]

        encoding_file = os.path.join(
            KNOWN_DIR,
            f"{visitor_id}.json"
        )

        with open(encoding_file, "w") as f:
            json.dump(
                encoding.tolist(),
                f
            )

        return jsonify({
            "success": True,
            "visitor_id": visitor_id
        })

    except Exception as e:

        return jsonify({
            "success": False,
            "message": str(e)
        })

    finally:

        if os.path.exists(temp_path):
            os.remove(temp_path)


@app.route("/identify-face", methods=["POST"])
def identify_face():

    if "image" not in request.files:
        return jsonify({
            "success": False,
            "message": "image missing"
        })

    image = request.files["image"]

    temp_path = os.path.join(
        UPLOAD_DIR,
        "identify.jpg"
    )

    image.save(temp_path)

    try:

        img = face_recognition.load_image_file(temp_path)

        encodings = face_recognition.face_encodings(img)

        if len(encodings) == 0:
            return jsonify({
                "success": False,
                "message": "No face detected"
            })

        unknown_face = encodings[0]

        best_match = None
        best_distance = 999

        for file in os.listdir(KNOWN_DIR):

            if not file.endswith(".json"):
                continue

            visitor_id = file.replace(".json", "")

            with open(
                os.path.join(KNOWN_DIR, file),
                "r"
            ) as f:

                known_encoding = np.array(
                    json.load(f)
                )

            distance = face_recognition.face_distance(
                [known_encoding],
                unknown_face
            )[0]

            if distance < best_distance:
                best_distance = distance
                best_match = visitor_id

        if best_match is None:

            return jsonify({
                "success": False,
                "message": "No registered faces"
            })

        confidence = round(
            (1 - best_distance) * 100,
            2
        )

        # Adjust threshold if needed
        if best_distance < 0.55:

            return jsonify({
                "success": True,
                "visitor_id": best_match,
                "confidence": confidence
            })

        return jsonify({
            "success": False,
            "message": "Unknown face"
        })

    except Exception as e:

        return jsonify({
            "success": False,
            "message": str(e)
        })

    finally:

        if os.path.exists(temp_path):
            os.remove(temp_path)


if __name__ == "__main__":

    app.run(
        host="0.0.0.0",
        port=5000,
        debug=True
    )