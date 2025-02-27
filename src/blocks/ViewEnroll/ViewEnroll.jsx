import React, { useState, useEffect } from "react";
import axios from "axios";
import { getApiLink } from "../../services/apiService";

const ViewEnroll = ({ groupItemId, onBack }) => {
  const [enrolledUsers, setEnrolledUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  // Modal state
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [newUser, setNewUser] = useState({ name: "", email: "" });

  useEffect(() => {
    fetchEnrollments();
  }, [groupItemId]);

  const fetchEnrollments = async () => {
    try {
      const response = await axios.post(
        getApiLink("get-user-enrollments-by-group-item-id"),
        { group_item_id: groupItemId },
        { headers: { "X-WP-Nonce": appLocalizer.nonce } }
      );

      setEnrolledUsers(response.data.enrollments);
      setLoading(false);
    } catch (error) {
      console.error("Error fetching enrollments:", error);
      setError("Failed to load enrollments.");
      setLoading(false);
    }
  };

  // Open/Close Modal
  const openModal = () => setIsModalOpen(true);
  const closeModal = () => setIsModalOpen(false);

  // Handle form input changes
  const handleChange = (e) => {
    setNewUser({ ...newUser, [e.target.name]: e.target.value });
  };

  // Handle form submission
  const handleSubmit = (e) => {
    e.preventDefault();

    // Add new user to the list (dummy implementation for now)
    setEnrolledUsers([
      ...enrolledUsers,
      {
        id: enrolledUsers.length + 1,
        name: newUser.name,
        email: newUser.email,
        date: new Date().toISOString().slice(0, 19).replace("T", " "),
      },
    ]);

    // Reset form and close modal
    setNewUser({ name: "", email: "" });
    closeModal();
  };

  return (
    <div className="enroll-container">
      <button onClick={onBack} className="back-btn">← Back to Groups</button>
      <h3>Enrollments</h3>

      {loading ? <p>Loading enrollments...</p> : error ? <p>{error}</p> : (
        <>
          {/* Add User Button */}
          <button onClick={openModal} className="add-user-btn">
            + Add User
          </button>

          {/* Enrollment Table */}
          <div className="table-container">
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Enrollment Date</th>
                </tr>
              </thead>
              <tbody>
                {enrolledUsers.length > 0 ? (
                  enrolledUsers.map((user, index) => (
                    <tr key={index}>
                      <td>{user.name}</td>
                      <td>{user.email}</td>
                      <td>{user.date}</td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan="3" className="no-data">No enrollment data available.</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </>
      )}

      {/* Modal for Adding User */}
      {isModalOpen && (
        <>
          <div className="modal-overlay" onClick={closeModal}></div>
          <div className="modal-content">
            <h2>Add New User</h2>
            <form onSubmit={handleSubmit}>
              <label>Name</label>
              <input type="text" name="name" value={newUser.name} onChange={handleChange} required />

              <label>Email</label>
              <input type="email" name="email" value={newUser.email} onChange={handleChange} required />

              <div className="modal-actions">
                <button type="submit" className="submit-btn">Add</button>
                <button type="button" className="cancel-btn" onClick={closeModal}>Cancel</button>
              </div>
            </form>
          </div>
        </>
      )}
    </div>
  );
};

export default ViewEnroll;
