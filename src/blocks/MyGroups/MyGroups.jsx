import React, { useState, useEffect } from "react";
import axios from "axios";

const MyGroups = () => {
  const [groups, setGroups] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const [newGroupName, setNewGroupName] = useState("");
  const [selectedProducts, setSelectedProducts] = useState([]);
  const [isModalOpen, setIsModalOpen] = useState(false);

  useEffect(() => {
    fetchGroups();
  }, []);

  const fetchGroups = async () => {
    try {
      const response = await axios.get("/wp-json/moowoodle/v1/get_user_groups");
      setGroups(response.data.groups || []);
      setLoading(false);
    } catch (err) {
      setError("Failed to fetch groups. Please try again later.");
      setLoading(false);
    }
  };

  const handleCreateGroup = async (e) => {
    e.preventDefault();
    // Implement API call to create a new group
    console.log("Creating group:", newGroupName, selectedProducts);
    setIsModalOpen(false);
  };

  if (loading) return <p>Loading groups...</p>;
  if (error) return <p>{error}</p>;

  return (
    <div>
      <h3>My Groups</h3>
      <button onClick={() => setIsModalOpen(true)}>Add Group</button>

      {groups.length > 0 ? (
        groups.map((group) => (
          <div key={group.group_id}>
            <h4>{group.group_name}</h4>
            <table>
              <thead>
                <tr>
                  <th>Product Name</th>
                  <th>Total Quantity</th>
                  <th>Available Quantity</th>
                  <th>Enroll User</th>
                </tr>
              </thead>
              <tbody>
                {group.products.map((item) => (
                  <tr key={item.product_id}>
                    <td>{item.product_name}</td>
                    <td>{item.total_quantity}</td>
                    <td>{item.available_quantity}</td>
                    <td>
                      <a href={item.enroll_url} target="_blank" rel="noopener noreferrer">
                        View
                      </a>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ))
      ) : (
        <p>You have no groups yet.</p>
      )}

      {isModalOpen && (
        <div className="modal">
          <h2>Add New Group</h2>
          <form onSubmit={handleCreateGroup}>
            <label>Group Name:</label>
            <input value={newGroupName} onChange={(e) => setNewGroupName(e.target.value)} required />

            <label>Select Products:</label>
            <select multiple onChange={(e) => setSelectedProducts([...e.target.selectedOptions].map(o => o.value))}>
              {/* Dynamically load products when API is available */}
              <option value="1">Sample Course 1</option>
              <option value="2">Sample Course 2</option>
            </select>

            <button type="submit">Create Group</button>
            <button type="button" onClick={() => setIsModalOpen(false)}>Cancel</button>
          </form>
        </div>
      )}
    </div>
  );
};

export default MyGroups;
